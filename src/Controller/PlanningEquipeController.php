<?php

namespace App\Controller;

use App\Entity\Equipe;
use App\Repository\EquipeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/equipes')]
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
    public function list(EquipeRepository $equipeRepository): JsonResponse
    {
        $equipes = $equipeRepository->findAll();
        return $this->json($equipes);

    }

    //POST /api/ equipes- Créer une équipe
    #[Route('', name: 'equipe_planning_create', methods: ['POST'])]
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
