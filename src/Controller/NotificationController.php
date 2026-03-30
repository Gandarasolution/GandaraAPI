<?php

namespace App\Controller;

use App\Entity\PlanningNotification;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/notifications', name: 'api_notifications_')]
class NotificationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ){}

    #[Route('/{id}', name: 'list', methods: ['GET'])]
    public function index(int $id): JsonResponse
    {
        $notifications = $this->entityManager->getRepository(PlanningNotification::class)->findBy(['employeeId' => $id]);
        return $this->json($notifications);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $notification = new PlanningNotification();
        // Assuming there are setters like setTitre, setContenu, etc.
        // $notification->setTitre($data['titre'] ?? '');

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        return $this->json($notification, 201);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(Request $request, PlanningNotification $notification): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Update fields here
        // $notification->setTitre($data['titre'] ?? $notification->getTitre());

        $this->entityManager->flush();

        return $this->json($notification);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(PlanningNotification $notification): JsonResponse
    {
        $this->entityManager->remove($notification);
        $this->entityManager->flush();

        return $this->json(null, 204);
    }
}
