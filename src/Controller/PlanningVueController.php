<?php

namespace App\Controller;

use App\Repository\PlanningVueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;

#[Route('/api/configs')]
#[OA\Tag(name: 'Configurations/Vues')]
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
    #[OA\Parameter(name: 'userId', in: 'path', description: 'ID de l\'utilisateur', schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'idPlanning', in: 'query', description: 'ID de planning (optionnel)', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Liste des configurations de l\'utilisateur')]
    public function getUserConfigs(int $userId, Request $request): JsonResponse
    {
        try {
            $IdPlanning = $request->query->get('idPlanning');
            $configs = $this->planningVueRepository->getConfigUser($userId, $IdPlanning);
            return $this->json(['error' => 0, 'data' => $configs]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération des configs pour l\'utilisateur {userId}: {message}', [
                'userId' => $userId,
                'message' => $e->getMessage(),
            ]);
            return $this->json(['error' => 1, 'message' => 'Une erreur est survenue lors de la récupération des configurations.'], 500);
        }
    }
}
