<?php

namespace App\Controller;

use App\Entity\PlanningNotification;
use App\Entity\Session;
use App\Repository\PlanningNotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/notifications', name: 'api_notifications_')]
#[OA\Tag(name: 'Notifications')]
class NotificationController extends AbstractController
{
    public function __construct(
        private PlanningNotificationRepository $planningNotificationRepository,
        private EntityManagerInterface $entityManager
    ){}

    #[Route('', name: 'list', methods: ['GET'])]
    #[OA\Parameter(name: 'id', in: 'path', description: 'ID de l\'employé pour lequel lister les notifications', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Liste des notifications de l\'employé')]
    public function index(#[CurrentUser] Session $user): JsonResponse
    {
        $id = $user->getIdpersonnel();
        $notifications = $this->planningNotificationRepository->getNotification($id);

        return $this->json(['data' => $notifications]);
    }

    /**
     * @throws \Exception
     */
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
        $data = json_decode($request->getContent(), true);

        $newNotificationId = $this->planningNotificationRepository->createNotification($data);


        return $this->json(['message' => 'Événement créé avec succès', 'IdPlanningNotification' => $newNotificationId], 201);

    }

    /**
     * @throws \Exception
     */
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

        $data = json_decode($request->getContent(), true);

        $lignesModifiees = $this->planningNotificationRepository->updateNotification($id, $data, $logger);

        if ($lignesModifiees === 0 || $lignesModifiees === null) {
            return $this->json(['message' => 'Notification non trouvée ou aucune modification apportée'], 404);
        }

        $logger->debug("Notification mise à jour - ID: $id, Lignes modifiées: $lignesModifiees");

        return $this->json(['message' => 'Notification mis à jour avec succès'], 201);

    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[OA\Parameter(name: 'id', in: 'path', description: 'ID de la notification à supprimer', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 204, description: 'Notification supprimée avec succès')]
    public function delete(PlanningNotification $notification): JsonResponse
    {
        $this->entityManager->remove($notification);
        $this->entityManager->flush();

        return $this->json([], 204);
    }


    /**
     * @throws \Exception
     */
    #[Route('/read', name: 'read', methods: ['PATCH'])]
    #[OA\RequestBody(
        description: 'Les informations pour marquer les notifications comme lues',
        required: true,
        content: new OA\JsonContent(type: 'object')
    )]
    #[OA\Response(response: 204, description: 'Notifications marquées comme lues avec succès')]
    #[OA\Response(response: 400, description: 'Requête invalide')]
    #[OA\Response(response: 500, description: 'Erreur lors de la mise à jour des notifications')]
    public function markAsRead(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $notificationIds = $data['notificationIds'] ?? [];

        if (empty($notificationIds)) {
            return $this->json(['message' => 'Aucun ID de notification fourni'], 400);
        }

        $this->planningNotificationRepository->markNotificationsAsRead($notificationIds);

        return $this->json([]);
    }
}
