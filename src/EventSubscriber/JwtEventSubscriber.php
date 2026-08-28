<?php

namespace App\EventSubscriber;

use App\Entity\Session;
use Doctrine\DBAL\Exception;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Doctrine\DBAL\Connection;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class JwtEventSubscriber implements EventSubscriberInterface
{

    public function __construct(private Connection $connection, private LoggerInterface $logger, private UrlGeneratorInterface $router)
    {
    }

    public static function getSubscribedEvents(): array
    {
        // On écoute l'événement de succès d'authentification de LexikJWT
        return [
            Events::AUTHENTICATION_SUCCESS => 'onAuthenticationSuccess',
        ];
    }

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        try {
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

            $data['permissions'] = (int)$planningDroit['IdDroitNiveau'] ?: 21; // Valeur par défaut si la requête ne retourne rien

            $data['planning'] = array_map(function($row) {
                // Default the image URL to null
                $imageUrl = null;

                // Only generate the URL if an image ID actually exists
                if (!empty($row['IdPlanningImage'])) {
                    $imageUrl = $this->router->generate('api_serve_image_file', [
                        'id' => $row['IdPlanningImage']
                    ], UrlGeneratorInterface::ABSOLUTE_URL);
                }

                return [
                    'IdPlanning'    => $row['IdPlanning'],
                    'NomPlanning'   => $row['NomPlanning'],
                    'PlanningImage' => ['id' => $row['IdPlanningImage'], 'image' => $imageUrl ]// Will be the absolute URL, or null if no image exists
                ];
            }, $planningAffectation);

            $data['error'] = 0;

            $event->setData($data);
        }catch (Exception $e) {

            $this->logger->debug('Erreur lors de la récupération des informations utilisateur après authentification, exception: ' . $e->getMessage());
            $event->setData([
                'error' => 1,
                'message' => 'Une erreur est survenue lors de la récupération des informations utilisateur.'
            ]);
        }

    }
}
