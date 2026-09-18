<?php

namespace App\EventSubscriber;

use App\Entity\Session;
use Doctrine\DBAL\Exception;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Mercure\Jwt\TokenFactoryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class JwtEventSubscriber implements EventSubscriberInterface
{

    public function __construct(
        private readonly Connection            $connection,
        private readonly LoggerInterface       $logger,
        private readonly UrlGeneratorInterface $router,
        #[Autowire(service: 'mercure.hub.default.jwt.factory')]
        private readonly TokenFactoryInterface $mercureTokenFactory,
        #[Autowire(env: 'JWT_TTL')]
        private int                            $jwtTtl
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        // On écoute l'événement de succès d'authentification de LexikJWT
        return [
            Events::AUTHENTICATION_SUCCESS => 'onAuthenticationSuccess',
        ];
    }

    /**
     * @throws \DateMalformedIntervalStringException
     * @throws Exception
     */
    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $data = $event->getData();
        $user = $event->getUser();

        if (!$user instanceof Session) {
            return;
        }

        $sql = '
                SELECT
                    COALESCE(S.NomSalarie, I.NomInterim) as NomEmploye,
                    COALESCE(S.PrenomSalarie, I.PrenomInterim) as PrenomEmployee
                FROM SESSION
                LEFT JOIN
                    Salarie S ON S.IdSalarie = IdPersonnel
                LEFT JOIN
                    Interim I ON I.IdInterim = IdPersonnel
                WHERE IdPersonnel = :id
            ';

        $employeInfos = $this->connection->fetchAssociative($sql, [
            'id' => $user->getIdpersonnel()
        ]);

        $sql = 'EXEC ps_PlanningDroitSelect @IdPersonnel = :id';
        $planningDroit = $this->connection->fetchAssociative($sql, [
            'id' => $user->getIdpersonnel()
        ]);
        $this->logger->debug("Requête pour récupérer les droits de l'utilisateur", ['IdPersonnel' => $user->getIdpersonnel(), 'PlanningDroit' => $planningDroit]);

        $sql = 'SELECT P.IdPlanning, NomPlanning, IdPlanningImage
                    FROM Planning P
                    LEFT JOIN PlanningAffectation PA ON P.IdPlanning = PA.IdPlanning
                    WHERE IdPersonnel = :id
            ';
        $planningAffectation = $this->connection->fetchAllAssociative($sql, [
            'id' => $user->getIdpersonnel()
        ]);
        $this->logger->debug("Requête pour récupérer les plannings affectés à l'utilisateur", ['IdPersonnel' => $user->getIdpersonnel(), 'PlanningAffectation' => $planningAffectation]);

        // 4. On prépare le tableau final à renvoyer au front
        $data['user'] = [
            'IdPersonnel' => $user->getIdpersonnel(),
        ];

        // 5. Si on a trouvé les infos, on les ajoute !
        if ($employeInfos) {
            $data['user']['Nom'] = $employeInfos['NomEmploye'];
            $data['user']['Prenom'] = $employeInfos['PrenomEmployee'];
        }

        $data['permissions'] = (int) ($planningDroit['IdDroitNiveau'] ?? 21);

        $data['planning'] = array_map(function ($row) {
            // Default the image URL to null
            $imageUrl = null;

            // Only generate the URL if an image ID actually exists
            if (!empty($row['IdPlanningImage'])) {
                $imageUrl = $this->router->generate('api_serve_image_file', [
                    'id' => $row['IdPlanningImage']
                ], UrlGeneratorInterface::ABSOLUTE_URL);
            }

            return [
                'IdPlanning' => $row['IdPlanning'],
                'NomPlanning' => $row['NomPlanning'],
                'PlanningImage' => ['id' => $row['IdPlanningImage'], 'image' => $imageUrl]// Will be the absolute URL, or null if no image exists
            ];
        }, $planningAffectation);


        $mercureToken = $this->mercureTokenFactory->create([
            'https://gandara.com/planning/update', // Topic public
            sprintf('http://gandara.com/user/%s', $user->getUserIdentifier()) // Topic privé exclusif à cet utilisateur
        ]);

        $cookieMercure = Cookie::create('mercureAuthorization')
            ->withValue($mercureToken)
            ->withHttpOnly(true)
            ->withSecure(true)
            ->withSameSite(Cookie::SAMESITE_NONE)
            ->withPath('/');

        $cookieLogged = Cookie::create('is_logged_in')
            ->withValue('true')
            ->withHttpOnly(false)
            ->withSecure(true)
            ->withSameSite(Cookie::SAMESITE_NONE)
            ->withPath('/')
            ->withExpires(new \DateTimeImmutable()->add(new \DateInterval('PT' . $this->jwtTtl . 'S')));

        $event->getResponse()->headers->setCookie($cookieMercure);
        $event->getResponse()->headers->setCookie($cookieLogged);

        // 6. Injection des données finales dans le JWT
        $event->setData($data);

    }
}
