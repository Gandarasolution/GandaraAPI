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

    #[Route('/{id}', name: 'app_planning_ressource_update', methods: ['PUT'])]
    public function upndatePlaningRessource(int $id, Request $request){
        try {
            $data = json_decode($request->getContent(), true);
            // Validation des données
            if (!isset($data['Libelle']) || !isset($data['Code'])) {
                return $this->json(['error' => 'Les champs Libelle et Code sont requis'], 400);
            }

            // Appel à la méthode de mise à jour dans le repository
            $result = $this->planningRessourceRepository->updateRessource($id, $data);

            if ($result) {
                return $this->json(['error' => 0, 'message' => 'Ressource mise à jour avec succès']);
            } else {
                return $this->json(['error' => 1, 'message' => 'Erreur lors de la mise à jour de la ressource'], 500);
            }
        } catch (\Exception $e) {
            return $this->json([
                'error' => 1,
                'message' => $e->getMessage()
            ], 500);
        }
    }

}
