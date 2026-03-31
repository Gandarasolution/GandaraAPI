<?php

namespace App\Controller;

use App\Entity\PlanningNotification;
use App\Repository\PlanningNotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/notifications', name: 'api_notifications_')]
class NotificationController extends AbstractController
{
    public function __construct(
        private PlanningNotificationRepository $planningNotificationRepository,
        private EntityManagerInterface $entityManager
    ){}

    #[Route('/{id}', name: 'list', methods: ['GET'])]
    public function index(int $id): JsonResponse
    {
        try {
            $notifications = $this->planningNotificationRepository->getNotification($id);

            return $this->json($notifications);

        }catch (\Exception $e){
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            $newNotificationId = $this->planningNotificationRepository->createNotification($data);


            return $this->json(['message' => 'Événement créé avec succès', 'IdPlanningNotification' => $newNotificationId], 201);
        }catch(\Exception $e){
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(int $id, Request $request, LoggerInterface $logger): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            $lignesModifiees = $this->planningNotificationRepository->updateNotification($id, $data, $logger);

            if ($lignesModifiees === 0 || $lignesModifiees === null) {
                return $this->json(['error' => 'Notification non trouvée ou aucune modification apportée'], 404);
            }

            $logger->debug("Notification mise à jour - ID: $id, Lignes modifiées: $lignesModifiees");

            return $this->json(['message' => 'Notification mis à jour avec succès'], 201);
        }catch (\Exception $e){
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(PlanningNotification $notification): JsonResponse
    {
        $this->entityManager->remove($notification);
        $this->entityManager->flush();

        return $this->json(null, 204);
    }
}
