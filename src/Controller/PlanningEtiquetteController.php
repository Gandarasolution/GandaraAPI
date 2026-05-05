<?php

namespace App\Controller;


use App\Repository\EquipeRepository;
use App\Repository\EtiquetteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;


#[Route('/api/etiquettes')]
#[OA\Tag(name: 'Étiquettes')]
class PlanningEtiquetteController extends AbstractController
{
    public function __construct(
        private EtiquetteRepository $repository,
        private EntityManagerInterface $entityManager
    ){}


    #[Route('/{idRessource}', name: 'etiquette_planning_list', methods: ['GET'])]
    #[OA\Parameter(name: 'idRessource', in: 'path', description: 'Identifiant numérique de la ressource', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Liste de toutes les étiquettes associées à la ressource')]
    public function list(int $idRessource): JsonResponse
    {
        if ($idRessource <= 0) {
            return $this->json(['error' => 1, 'message' => 'ID de ressource invalide'], 400);
        }
        try {
            $result = $this->repository->findEtiquetteByIdRessource($idRessource);

            return $this->json(['error' => 0, 'data' => $result]);

        }catch ( \Exception $e ) {
            return new JsonResponse(['message' => $e->getMessage(), 'error' => 1], 500);
        }
    }

    #[Route('', name: 'etiquette_planning_create', methods: ['POST'])]
    #[OA\Response(response: 200, description: 'Création d\'une nouvelle étiquette pour une ressource')]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = $request->toArray();

            $libelleCourt = $data['LibelleCourtPlanningEtiquette'] ?? null;
            $libelleLong = $data['LibelleLongPlanningEtiquette'] ?? null;

            if (!isset($data['IdPlanningRessource']) || !is_int($data['IdPlanningRessource']) || $data['IdPlanningRessource'] <= 0) {
                return $this->json(['error' => 1, 'message' => 'ID de ressource invalide ou manquant'], 400);
            }
            if ((!isset($libelleLong) || !is_string($libelleLong) || empty(trim($libelleLong)))
                && (!isset($libelleCourt) || !is_string($libelleCourt) || empty(trim($libelleCourt)))) {
                return $this->json(['error' => 1, 'message' => 'Au moins un libellé (long ou court) doit être fourni et non vide'], 400);
            }

            $result = $this->repository->createEtiquette($data);

            return $this->json(['error' => 0, 'data' => $result]);

        }catch ( \Exception $e ) {
            return new JsonResponse(['message' => $e->getMessage(), 'error' => 1], 500);
        }
    }
}
