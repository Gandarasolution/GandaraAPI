<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class MercureNotificationService
{
    private const BASE_TOPIC = "https://gandara.com/planning/";

    public function __construct(private HubInterface $hub, private LoggerInterface $logger) {}

    /**
     * Envoie une  notificationen temps réel aux autres utilisateurs du planning.
     * * @param int    $idPlanning L'ID du planning concerné
     * @param string $action     L'action effectuée (ex: 'APPOINTMENT_UPDATED')
     * @param int    $updatedBy  L'ID de l'utilisateur qui a fait l'action
     * @param array  $data       Les données de l'événement (ID, dates, etc.)
     */
    public function notifyPlanningChange(int $idPlanning, string $action, int $updatedBy, array $data = []): void
    {
        $topic = self::BASE_TOPIC . $idPlanning;

        $payload = json_encode([
            'action'    => $action,
            'updatedBy' => $updatedBy,
            'data'      => $data
        ]);

        $update = new Update($topic, $payload);

        try {
            $this->hub->publish($update);
        } catch (\Exception $e) {
            $this->logger->error("Impossible d'envoyer la notif depuis Mercure: " . $e->getMessage(), [
                'topic' => $topic,
                'payload' => $payload
            ]);
        }
    }
}
