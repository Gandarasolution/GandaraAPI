<?php

namespace App\EventSubscriber;

use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\TokenExtractorInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class JwtRefreshSubscriber implements EventSubscriberInterface
{


    public function __construct(
        private JWTTokenManagerInterface         $jwtManager,
        private TokenStorageInterface            $tokenStorage,
        private readonly TokenExtractorInterface $tokenExtractor,
        #[Autowire(env: 'JWT_NAME')]
        private readonly string                  $cookieName,
        #[Autowire(env: 'JWT_TTL')]
        private readonly int                     $jwtTtl
    ) {}

    public static function getSubscribedEvents(): array
    {
        // Priorité basse pour s'assurer que la réponse est prête
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', -10],
        ];
    }

    /**
     * @throws \DateMalformedIntervalStringException
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        // Ignorer les sous-requêtes
        if (!$event->isMainRequest()) {
            return;
        }

        $token = $this->tokenStorage->getToken();

        // Si l'utilisateur n'est pas authentifié, on ne fait rien
        if (!$token || !$token->getUser()) {
            return;
        }

        $rawToken = $this->tokenExtractor->extract($event->getRequest());

        if (!$rawToken) {
            return;
        }

        try {
            $payload = $this->jwtManager->parse($rawToken);
        } catch (JWTDecodeFailureException $e) {
            return;
        }

        if (!$payload || !isset($payload['exp'])) {
            return;
        }

        $timeRemaining = $payload['exp'] - time();

        if ($timeRemaining > 180) {
            return;
        }

        $expiresAt = time() + $this->jwtTtl;


        // 1. Générer le nouveau JWT avec le compteur remis à 0
        $newJwt = $this->jwtManager->create($token->getUser());

        // 2. Créer le nouveau cookie HttpOnly
        $cookie = Cookie::create($this->cookieName)
            ->withValue($newJwt)
            ->withHttpOnly(true)
            ->withSecure(true) // À passer à 'false' si tu testes en local sans HTTPS
            ->withSameSite('lax')
            ->withExpires(
                new \DateTimeImmutable()->setTimestamp($expiresAt)
            );


        $response = $event->getResponse();

        // 3. Ajouter le cookie à la réponse
        $response->headers->setCookie($cookie);

        // Informe le frontend qu'un refresh vient d'avoir lieu
        $response->headers->set(
            'X-Token-Expires-At',
            (string) $expiresAt
        );
    }
}
