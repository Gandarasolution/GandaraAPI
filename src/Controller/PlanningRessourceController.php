<?php

namespace App\Controller;

use App\Repository\PlanningRessourceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route("/api/ressources")]
class PlanningRessourceController extends abstractController
{

    public function __construct(
        private PlanningRessourceRepository $planningRessourceRepository,
    )
    {

    }

    ///api/ressource/search?q=q&types=type1,type2&limit=20
    #[Route('/search', name: 'app_planning_ressource_search', methods: ['GET'])]
    public function getPlanningRessources(Request $request){
        try {
            $query = $request->query->get('q', '');
            $limit = $request->query->get('limit', 20);
            $type = $request->query->get('types', '');

            $result = $this->planningRessourceRepository->getRessource($query, $limit, $type);

            return $this->json(['error' => 0, 'data' => $result]);
        }catch (\Exception $e) {
            return $this->json([
                'error' => 1,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
