<?php

namespace App\Controller;

use App\Repository\PlanningRessourceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;

#[Route("/api/ressources")]
#[OA\Tag(name: 'Ressources')]
class PlanningRessourceController extends abstractController
{

    public function __construct(
        private PlanningRessourceRepository $planningRessourceRepository,
    )
    {

    }

    ///api/ressource/search?q=q&types=type1,type2&limit=20
    #[Route('/search', name: 'app_planning_ressource_search', methods: ['GET'])]
    #[OA\Parameter(name: 'q', in: 'query', description: 'Recherche par nom', schema: new OA\Schema(type: 'string', default: ''))]
    #[OA\Parameter(name: 'limit', in: 'query', description: 'Limite de résultats', schema: new OA\Schema(type: 'integer', default: 20))]
    #[OA\Parameter(name: 'types', in: 'query', description: 'Types de ressources (ex: type1,type2)', schema: new OA\Schema(type: 'string', default: ''))]
    #[OA\Response(response: 200, description: 'Liste des ressources correspondantes')]
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
    #[OA\Parameter(name: 'id', in: 'path', description: 'ID de la ressource à modifier', schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(
        description: 'Les nouvelles informations de la ressource',
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'Libelle', type: 'string'),
                new OA\Property(property: 'Code', type: 'string'),
                new OA\Property(property: 'Actif', type: 'boolean')
            ],
            type: 'object'
        )
    )]
    #[OA\Response(response: 200, description: 'Ressource mise à jour avec succès')]
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
