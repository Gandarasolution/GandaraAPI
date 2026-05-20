<?php

namespace App\Controller;

use App\Repository\EquipeRepository;
use App\Repository\PoleActiviteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

#[Route('/api/pole-activites')]
#[OA\Tag(name: 'Équipes/Ressources')]
class PlanningPoleActiviteController extends AbstractController
{

    public function __construct(
        private PoleActiviteRepository $repository,
        private EntityManagerInterface $entityManager
    ){}

    #[Route('', name: 'poleActivite_planning_list', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Liste de touts les poles d\'activité')]
    public function list(): JsonResponse
    {
        $result = $this->repository->findAll();
        return $this->json(['error' => 0, 'data' => $result]);

    }
}
