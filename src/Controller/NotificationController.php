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
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;

#[Route('/api/notifications', name: 'api_notifications_')]
#[OA\Tag(name: 'Notifications')]
class NotificationController extends AbstractController
{
    public function __construct(
        private PlanningNotificationRepository $planningNotificationRepository,
        private EntityManagerInterface $entityManager
    ){}

    #[Route('/{id}', name: 'list', methods: ['GET'])]
    #[OA\Parameter(name: 'id', in: 'path', description: 'ID de l\'employé pour lequel lister les notifications', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Liste des notifications de l\'employé')]
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
    #[OA\RequestBody(
        description: 'Les informations pour créer une notification',
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'IdEmploye', type: 'integer'),
                new OA\Property(property: 'Message', type: 'string'),
                new OA\Property(property: 'Type', type: 'string')
            ],
            type: 'object'
        )
    )]
    #[OA\Response(response: 201, description: 'Notification créée avec succès')]
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
    #[OA\Parameter(name: 'id', in: 'path', description: 'ID de la notification à modifier', schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(
        description: 'Les nouvelles informations de la notification',
        required: true,
        content: new OA\JsonContent(type: 'object')
    )]
    #[OA\Response(response: 201, description: 'Notification mise à jour avec succès')]
    #[OA\Response(response: 404, description: 'Notification non trouvée')]
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
    #[OA\Parameter(name: 'id', in: 'path', description: 'ID de la notification à supprimer', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 204, description: 'Notification supprimée avec succès')]
    public function delete(PlanningNotification $notification): JsonResponse
    {
        $this->entityManager->remove($notification);
        $this->entityManager->flush();

        return $this->json(null, 204);
    }
}
