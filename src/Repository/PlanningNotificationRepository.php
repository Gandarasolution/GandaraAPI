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


    /**
     * @throws Exception
     */
    public function createNotification(mixed $data)
    {
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
            throw new \RuntimeException("La création de la notification a échoué (aucun ID retourné).");
        }
        return $result;
    }

    /**
     * @throws Exception
     */
    public function updateNotification(int $id, mixed $data, LoggerInterface $logger)
    {
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
    }

    /**
     * @throws Exception
     */
    public function getNotification(int $id): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'EXEC ps_PlanningNotificationsSelectBySession @IdPersonnel = :IdPersonnel';
        $params = ['IdPersonnel' => $id];

        $result = $conn->executeQuery($sql, $params)->fetchAllAssociative();

        foreach ($result as &$row) {
            $row['IsRead'] = $row['IsRead'] === 1;
        }
        unset($row);

        return $result;
    }

    /**
     * @throws Exception
     */
    public function markNotificationsAsRead(mixed $notificationIds)
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'EXEC ps_PlanningNotificationsUpdateRead @NotificationIds = :NotificationIds';
        $params = ['NotificationIds' => implode(',', $notificationIds)];

        return $conn->executeQuery($sql, $params)->fetchOne();

    }
}
