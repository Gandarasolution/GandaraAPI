<?php

namespace App\Controller;

use App\Repository\PlanningVueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/configs')]
class PlanningVueController extends AbstractController
{

    public function __construct(
        private readonly LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private PlanningVueRepository $planningVueRepository,
    )
    {}

    //GET /api/configs/user/:userId?idPlanning=:id- Configs d'un utilisateur
    #[Route('/user/{userId}', name: 'api_configs_user', methods: ['GET'])]
    public function getUserConfigs(int $userId, Request $request): JsonResponse
    {
        try {
            $IdPlanning = $request->query->get('idPlanning');
            $configs = $this->planningVueRepository->getConfigUser($userId, $IdPlanning);
            return $this->json($configs);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération des configs pour l\'utilisateur {userId}: {message}', [
                'userId' => $userId,
                'message' => $e->getMessage(),
            ]);
            return $this->json(['error' => 'Une erreur est survenue lors de la récupération des configurations.'], 500);
        }
    }
}
