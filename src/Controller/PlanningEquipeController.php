<?php

namespace App\Controller;

use App\Entity\Equipe;
use App\Repository\EquipeRepository;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

#[Route('/api/equipes')]
#[OA\Tag(name: 'Équipes/Ressources')]
class PlanningEquipeController extends AbstractController
{

    /**
     * @param EquipeRepository $repository
     */
    public function __construct(
        private readonly EquipeRepository $repository,
    ){}

    //GET /api/equipes- Lister toutes les équipes

    /**
     * @throws Exception
     */
    #[Route('', name: 'equipe_planning_list', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Liste de toutes les équipes')]
    public function list(Request $request): JsonResponse
    {
        $idPlanningVue = $request->headers->get('X-PlanningVue-Id');


        $equipes = $this->repository->getAllEquipes((int)$idPlanningVue);


        return $this->json(['data' => $equipes]);

    }

    //POST /api/equipes - Créer une équipe
    #[Route('', name: 'equipe_planning_create', methods: ['POST'])]
    #[OA\RequestBody(
        description: 'Les informations pour créer une équipe',
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'Name', type: 'string', description: 'Le nom de l\'équipe')
            ],
            type: 'object'
        )
    )]
    #[OA\Response(response: 200, description: 'Équipe créée avec succès')]
    public function create(Request $request): JsonResponse
    {
        // Logique de création d'une équipe à partir des données de la requête

        return $this->json(['message' => 'Équipe créée avec succès']);

    }

    //PUT /api/equipes/:id- Modifier une équipe
    #[Route('/{id}', name: 'equipe_planning_update', methods: ['PUT'])]
    #[OA\Parameter(name: 'id', in: 'path', description: 'ID de l\'équipe', schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(
        description: 'Les nouvelles informations de l\'équipe',
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'Name', type: 'string', description: 'Le nouveau nom de l\'équipe')
            ],
            type: 'object'
        )
    )]
    #[OA\Response(response: 200, description: 'Équipe modifiée avec succès')]
    #[OA\Response(response: 404, description: 'Équipe non trouvée')]
    public function update(Request $request, int $id): JsonResponse
    {
//        if (!$this->repository->find($id)) {
//            return $this->json(['error' => 'Équipe non trouvée'], 404);
//        }
        // Logique de mise à jour d'une équipe à partir des données de la requête
//            $data = json_decode($request->getContent(), true);
//            $equipe = $this->repository->find($id);
//            $equipe->setDesignationequipe($data['Name']);
//
//            $this->entityManager->persist($equipe);
//            $this->entityManager->flush();

        return $this->json(['message' => 'Équipe mise à jour avec succès']);


    }

}
