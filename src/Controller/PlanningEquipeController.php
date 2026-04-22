<?php

namespace App\Controller;

use App\Entity\Equipe;
use App\Repository\EquipeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;

#[Route('/api/equipes')]
#[OA\Tag(name: 'Équipes/Ressources')]
class PlanningEquipeController extends AbstractController
{

    /**
     * @param EquipeRepository $repository
     */
    public function __construct(
        private EquipeRepository $repository,
        private EntityManagerInterface $entityManager
    ){}

    //GET /api/equipes- Lister toutes les équipes
    #[Route('', name: 'equipe_planning_list', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Liste de toutes les équipes')]
    public function list(EquipeRepository $equipeRepository): JsonResponse
    {
        $equipes = $equipeRepository->findAll();
        return $this->json($equipes);

    }

    //POST /api/ equipes- Créer une équipe
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
        try {
            // Logique de création d'une équipe à partir des données de la requête
            $data = json_decode($request->getContent(), true);
            $equipe = new Equipe();
            $equipe->setDesignationequipe($data['Name']);

            $this->entityManager->persist($equipe);
            $this->entityManager->flush();

            return $this->json(['message' => 'Équipe créée avec succès']);
        }catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de la création de l\'équipe: ' . $e->getMessage()], 500);
        }

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
        if (!$this->repository->find($id)) {
            return $this->json(['error' => 'Équipe non trouvée'], 404);
        }
        try {
            // Logique de mise à jour d'une équipe à partir des données de la requête
            $data = json_decode($request->getContent(), true);
            $equipe = $this->repository->find($id);
            $equipe->setDesignationequipe($data['Name']);

            $this->entityManager->persist($equipe);
            $this->entityManager->flush();

            return $this->json(['message' => 'Équipe mise à jour avec succès']);
        }
        catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de la mise à jour de l\'équipe: ' . $e->getMessage()], 500);
        }
    }

}
