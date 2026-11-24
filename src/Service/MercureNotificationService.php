<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class MercureNotificationService
{
    private const BASE_TOPIC = "https://gandara.com/planning/update";

    public function __construct(
        private readonly HubInterface $hub,
        private readonly LoggerInterface $logger
    ) {}

    public function notifyPlanningChange(int $idPlanning, string $action, int $updatedBy, array $data = []): void
    {
        $this->publish(self::BASE_TOPIC, [
            'action'    => $action,
            'updatedBy' => $updatedBy,
            'data'      => $data,
        ]);
    }

    public function notifyEmployeeLockStatus(
        int $employeeId,
        bool $isLocked,
        int $userId,
        ?string $userNom = null,
        ?int $timestamp = null
    ): void {
        $resolvedTimestamp = $timestamp ?? (int) round(microtime(true) * 1000);

        $this->publish(self::BASE_TOPIC, [
            'action'    => $isLocked ? 'EMPLOYEE_LOCKED' : 'EMPLOYEE_UNLOCKED',
            'updatedBy' => $userId,
            'data'      => [
                'employeeId' => $employeeId,
                'isLocked'   => [
                    'status'       => $isLocked,
                    'timestamp'    => $resolvedTimestamp,
                    'updatedById'  => $isLocked ? $userId : null,
                    'updatedByNom' => $isLocked ? $userNom : null,
                ],
            ],
        ]);
    }

    public function notifyEmployeeTeamUpdated(int $employeeId, ?int $teamId, int $updatedBy): void
    {
        $this->publish(self::BASE_TOPIC, [
            'action'    => 'EMPLOYEE_TEAM_UPDATED',
            'updatedBy' => $updatedBy,
            'data'      => [
                'employeeId' => $employeeId,
                'idEquipe'   => $teamId,
            ],
        ]);
    }

    public function notifyTeamLockStatus(int $teamId, bool $isLocked, int $userId): void
    {
        $this->publish(self::BASE_TOPIC, [
            'action'    => $isLocked ? 'TEAM_LOCKED' : 'TEAM_UNLOCKED',
            'updatedBy' => $userId,
            'data'      => [
                'teamId'   => $teamId,
                'isLocked' => $isLocked,
                'lockedBy' => $isLocked ? $userId : null,
            ],
        ]);
    }


    public function notifyTeamChange(string $action, int $updatedBy, array $data = []): void
    {
        $this->publish(self::BASE_TOPIC, [
            'action'    => $action, // 'TEAM_CREATED' | 'TEAM_UPDATED' | 'TEAM_DELETED'
            'updatedBy' => $updatedBy,
            'data'      => $data,
        ]);
    }

    private function publish(string $topic, array $payloadData): void
    {
        $payload = json_encode($payloadData);
        $update = new Update($topic, $payload);

        try {
            $this->hub->publish($update);
        } catch (\Throwable $e) {
            $previous = $e->getPrevious();
            $responseBody = null;
            $statusCode = null;

            // Si l'erreur vient d'une réponse HTTP (401, 403, 404, 500...)
            if ($previous instanceof HttpExceptionInterface) {
                $response = $previous->getResponse();
                $statusCode = $response->getStatusCode();
                $responseBody = $response->getContent(false); // false = ne relance pas d'exception
            }

            $this->logger->error("Impossible d'envoyer la notif Mercure : " . $e->getMessage(), [
                'topic'         => $topic,
                'payload'       => $payload,
                'previous_msg'  => $previous?->getMessage(),
                'previous_type' => $previous ? get_class($previous) : null,
                'http_status'   => $statusCode,
                'response_body' => $responseBody,
                'trace'         => $e->getTraceAsString(),
            ]);
        }
    }
}
