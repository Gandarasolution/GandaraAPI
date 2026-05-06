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

#[Route('/api/planning')]
#[OA\Tag(name: 'Configurations/Vues')]
class PlanningVueController extends AbstractController
{

    public function __construct(
        private readonly LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private PlanningVueRepository $planningVueRepository,
    )
    {}

    //GET /api/planning/vue/user/:userId?idPlanning=:id- Configs d'un utilisateur
    #[Route('/vue/user/{userId}', name: 'api_configs_user', methods: ['GET'])]
    #[OA\Parameter(name: 'userId', in: 'path', description: 'ID de l\'utilisateur', schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'idPlanning', in: 'query', description: 'ID de planning (optionnel)', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Liste des configurations de l\'utilisateur et des jours non travaillé du planning')]
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
            return $this->json(['error' => 1, 'message' => 'Une erreur est survenue lors de la récupération des configurations:' . $e->getMessage()], 500);
        }
    }

    // POST /api/planning/{idPlanning}/non-working-dates Ajout d'un jour non travaillé
    #[Route('/{idPlanning}/non-working-dates', name: 'ap i_non-working-dates', methods: ['POST'])]
    public function addNonWorkingDates(Request $request, int $idPlanning, LoggerInterface $logger): JsonResponse
    {
        try {
            $data = $request->toArray();

            $result = $this->planningVueRepository->createNonWorkingDates($data, $idPlanning, $logger);

            return $this->json(['error' => 0, 'message' => 'Jours non travaillés ajoutés avec succès.', 'data' => $result]);
        } catch (\Exception $e) {
            return $this->json(['error' => 1, 'message' =>  $e->getMessage()], 500);
        }
    }


    #[Route('/{idDate}/non-working-dates', name: 'api_non-working-dates', methods: ['DELETE'])]
    public function deleteNonWorkingDates(Request $request, int $idDate): JsonResponse
    {
        try {

            $result = $this->planningVueRepository->deleteNonWorkingDates($idDate);

            return $this->json(['error' => 0, 'message' => 'Jours non travaillé supprimé avec succès.']);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la suppression d\'un jour non travaillé: {message}', [
                'message' => $e->getMessage(),
            ]);
            return $this->json(['error' => 1, 'message' =>  $e->getMessage()], 500);
        }
    }
}
