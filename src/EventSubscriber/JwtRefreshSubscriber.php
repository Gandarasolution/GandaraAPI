<?php

namespace App\EventSubscriber;

use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\TokenExtractorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class JwtRefreshSubscriber implements EventSubscriberInterface
{
    private JWTTokenManagerInterface $jwtManager;
    private TokenStorageInterface $tokenStorage;
    private string $cookieName;
    private int $jwtTtl;

    public function __construct(
        JWTTokenManagerInterface $jwtManager,
        TokenStorageInterface $tokenStorage,
        private TokenExtractorInterface $tokenExtractor,
        string $cookieName,
        int $jwtTtl
    ) {
        $this->jwtManager = $jwtManager;
        $this->tokenStorage = $tokenStorage;
        $this->cookieName = $cookieName;
        $this->jwtTtl = $jwtTtl;
    }

    public static function getSubscribedEvents(): array
    {
        // Priorité basse pour s'assurer que la réponse est prête
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', -10],
        ];
    }

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


        // 1. Générer le nouveau JWT avec le compteur remis à 0
        $newJwt = $this->jwtManager->create($token->getUser());

        // 2. Créer le nouveau cookie HttpOnly
        $cookie = Cookie::create($this->cookieName)
            ->withValue($newJwt)
            ->withHttpOnly(true)
            ->withSecure(true) // À passer à 'false' si tu testes en local sans HTTPS
            ->withSameSite('lax')
            ->withExpires((new \DateTime())->add(new \DateInterval('PT' . $this->jwtTtl . 'S')));

        // 3. Ajouter le cookie à la réponse
        $event->getResponse()->headers->setCookie($cookie);

        // 4. Créer le cookie is_logged_in pour indiquer que l'utilisateur est connecté
        $cookie = Cookie::create('is_logged_in')
            ->withValue('true')
            ->withHttpOnly(false)
            ->withSecure(true)
            ->withSameSite(Cookie::SAMESITE_NONE)
            ->withPath('/')
            ->withExpires((new \DateTime())->add(new \DateInterval('PT' . $this->jwtTtl . 'S')));

        $event->getResponse()->headers->setCookie($cookie);
    }
}
