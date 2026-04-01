<?php

namespace App\EventSubscriber;

use App\Entity\Session;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Doctrine\DBAL\Connection;

class JwtEventSubscriber implements EventSubscriberInterface
{

    public function __construct(private Connection $connection)
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

            // 3. On fait notre requête manuelle (ex: jointure ou procédure stockée)
            // Ici, j'utilise la connexion DBAL comme tu en as l'habitude !
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

            // 4. On prépare le tableau final à renvoyer au front
            $data['user'] = [
                'IdPersonnel' => $user->getIdpersonnel(),
            ];

            // 5. Si on a trouvé les infos, on les ajoute !
            if ($employeInfos) {
                $data['user']['nom'] = $employeInfos['NomEmploye'];
                $data['user']['prenom'] = $employeInfos['PrenomEmployee'];
            }

            $data['error'] = 0;

            $event->setData($data);
        }catch (\Exception $e) {
            // En cas d'erreur, on peut logguer ou faire ce qu'on veut
            // Ici, je choisis de renvoyer une erreur générique au front
            $event->setData([
                'error' => 1,
                'message' => 'Une erreur est survenue lors de la récupération des informations utilisateur.'
            ]);
        }

    }
}
