<?php

namespace App\Repository;

use App\Entity\PlanningNotification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\Exception;
use Psr\Log\LoggerInterface;

/**
 * @extends ServiceEntityRepository<PlanningNotification>
 */
class PlanningNotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlanningNotification::class);
    }


    public function createNotification(mixed $data)
    {
        try {
            $conn = $this->getEntityManager()->getConnection();
            $sql = 'EXEC ps_PlanningNotificationUpdateInsert @Type = :Type, @Titre = :Titre, @Libelle = :Libelle, @LabelTypeNotifications = :LabelTypeNotifications, @IdPersonnel  = :IdPersonnel';
            $params = [
                'Type' => 'INSERT',
                'Titre' => $data['Titre'],
                'Libelle' => $data['Libelle'],
                'LabelTypeNotifications' => $data['LabelTypeNotifications'],
                'IdPersonnel' => $data['IdPersonnel']
            ];
            $result = $conn->executeQuery($sql, $params)->fetchOne();

            if (!$result) {
                throw new \Exception("Erreur : l'événement n'a pas pu être créé.");
            }
            return $result;

        } catch (Exception $e) {
            throw new \Exception("Erreur lors de la création de la notification : " . $e->getMessage());
        }
    }

    public function updateNotification(int $id, mixed $data, LoggerInterface $logger)
    {
        try {
            $conn = $this->getEntityManager()->getConnection();
            $sql = 'EXEC ps_PlanningNotificationUpdateInsert @Type = :Type, @Titre = :Titre, @Libelle = :Libelle, @LueNotifications = :LueNotifications, @IdPlanningNotification = :IdPlanningNotification';


            $logger->debug("Update Notification - ID: $id, Data: " . json_encode($data));
            $logger->debug((int) $data['LueNotifications']);
            $params = [
                'Type' => 'UPDATE',
                'Titre' => $data['Titre'] ?? null,
                'Libelle' => $data['Libelle'] ?? null,
                'LueNotifications' => isset($data['LueNotifications']) ? (int) $data['LueNotifications'] : null,
                'IdPlanningNotification' => $id
            ];

            return $conn->executeQuery($sql, $params)->fetchOne();
        } catch (Exception $e) {
            throw new \Exception("Erreur lors de la mise à jour de la notification : " . $e->getMessage());
        }
    }

    public function getNotification(int $id)
    {
        try {
            $conn = $this->getEntityManager()->getConnection();
            $sql = 'EXEC ps_PlanningNotificationsSelectBySession @IdPersonnel = :IdPersonnel';
            $params = ['IdPersonnel' => $id];

            return $conn->executeQuery($sql, $params)->fetchAllAssociative();
        } catch (Exception $e) {
            throw new \Exception("Erreur lors de la récupération des notifications : " . $e->getMessage());
        }
    }
}
