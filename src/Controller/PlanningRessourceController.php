<?php

namespace App\Controller;

use App\Entity\Session;
use App\Repository\PlanningRessourceRepository;
use App\Repository\SecurityRepository;
use App\Service\MercureNotificationService;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route("/api/ressources")]
#[OA\Tag(name: 'Ressources')]
class PlanningRessourceController extends abstractController
{

    public function __construct(
        private MercureNotificationService $notifier,
        private PlanningRessourceRepository $planningRessourceRepository,
        private SecurityRepository $securityRepository
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

            $result = $this->planningRessourceRepository->getRessources($query, $limit, $type);

            return $this->json(['error' => 0, 'data' => $result]);
        }catch (\Exception $e) {
            return $this->json([
                'error' => 1,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    #[Route('/projets', name: 'app_planning_ressource_projets', methods: ['GET'])]
    #[OA\Parameter(name: 'limit', in: 'query', description: 'Limite de résultats', schema: new OA\Schema(type: 'integer', default: 20))]
    #[OA\Parameter(name: 'pageNum', in: 'query', description: 'Numéro de page pour la pagination', schema: new OA\Schema(type: 'integer', default: 1))]
    #[OA\Parameter(name: 'q', in: 'query', description: '', schema: new OA\Schema(type: 'string', default: ''))]
    #[OA\Parameter(name: 'chargeAffaire', in: 'query', description: 'Filtre sur les chargée d\'affaire', schema: new OA\Schema(type: 'string', default: ''))]
    #[OA\Parameter(name: 'chefChantier', in: 'query', description: 'Filtre sur les chefs de chantiers', schema: new OA\Schema(type: 'string', default: ''))]
    #[OA\Parameter(name: 'code', in: 'query', description: 'Filtre sur les codes d\'identification', schema: new OA\Schema(type: 'string', default: ''))]
    #[OA\Parameter(name: 'etat', in: 'query', description: 'Filtre sur les état', schema: new OA\Schema(type: 'string', default: ''))]
    #[OA\Response(response: 200, description: 'Liste des projets')]
    public function getProjet(Request $request, LoggerInterface $logger, #[CurrentUser] Session $user)
    {

        $droit = $this->securityRepository->getPermission($user, $logger);
        if (!in_array($droit, [22, 23])) {
            return $this->json([
                'error' => 1,
                'message' => 'Vous n\'avez pas la permission de voir les projets.'
            ], 403);
        }

        try {
            $limit = $request->query->get('limit', 20);
            $pageNumber = $request->query->get('pageNum', 1);
            $q = $request->query->get('q', '');

            $chargeeAffaires = $request->query->get('chargeAffaire', "");
            $chefChantiers = $request->query->get('chefChantier', "");
            $codes = $request->query->get('code',"");
            $etats = $request->query->get('etat', "");

            $logger->debug('Récupération des projets avec les paramètres',
                [
                '@Limit' => $limit,
                '@PageNumber' => $pageNumber,
                '@Query' => $q,
                '@ChargeeAffaires' => $chargeeAffaires,
                '@ChefChantiers' => $chefChantiers,
                '@Codes' => $codes,
                '@Etats' => $etats
            ]);

            $result = $this->planningRessourceRepository->getProjet(
                $limit,
                $pageNumber,
                $q,
                $chargeeAffaires,
                $chefChantiers,
                $codes,
                $etats,
                $logger
            );

            return $this->json(['error' => 0, 'data' => $result['data'], 'TotalLignes' => $result['TotalLignes']]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 1,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    #[Route('/rubrique-paie', name: 'app_planning_ressource_rubrique-paie', methods: ['GET'])]
    #[OA\Parameter(name: 'limit', in: 'query', description: 'Limite de résultats', schema: new OA\Schema(type: 'integer', default: 20))]
    #[OA\Parameter(name: 'pageNum', in: 'query', description: 'Numéro de page pour la pagination', schema: new OA\Schema(type: 'integer', default: 1))]
    #[OA\Parameter(name: 'q', in: 'query', description: '', schema: new OA\Schema(type: 'string', default: ''))]
    #[OA\Parameter(name: 'code', in: 'query', description: 'Filtre sur les codes d\'identification', schema: new OA\Schema(type: 'string', default: ''))]
    #[OA\Response(response: 200, description: 'Liste des rubrique de paie')]
    public function getRubriquePaie(Request $request, LoggerInterface $logger,  #[CurrentUser] Session $user)
    {

        $droit = $this->securityRepository->getPermission($user, $logger);
        if ($droit != 23) {
            return $this->json([
                'error' => 1,
                'message' => 'Vous n\'avez pas la permission de voir les rubrique de paie.'
            ], 403);
        }

        try {
            $limit = $request->query->get('limit', 20);
            $pageNumber = $request->query->get('pageNum', 1);
            $q = $request->query->get('q', '');
            $codes = $request->query->get('code','');

            $logger->debug('Récupération des rubriques de paie avec les paramètres',
                [
                    '@Limit' => $limit,
                    '@PageNumber' => $pageNumber,
                    '@Query' => $q,
                    '@Codes' => $codes,
                ]);

            $result = $this->planningRessourceRepository->getRubriquePaie(
                $limit,
                $pageNumber,
                $q,
                $codes,
                $logger
            );

            return $this->json(['error' => 0, 'data' => $result['data'], 'TotalLignes' => $result['TotalLignes']]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 1,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/manual-events', name: 'app_planning_ressource_manuel', methods: ['GET'])]
    #[OA\Parameter(name: 'limit', in: 'query', description: 'Limite de résultats', schema: new OA\Schema(type: 'integer', default: 20))]
    #[OA\Parameter(name: 'pageNum', in: 'query', description: 'Numéro de page pour la pagination', schema: new OA\Schema(type: 'integer', default: 1))]
    #[OA\Parameter(name: 'q', in: 'query', description: '', schema: new OA\Schema(type: 'string', default: ''))]
    #[OA\Parameter(name: 'code', in: 'query', description: 'Filtre sur les codes d\'identification', schema: new OA\Schema(type: 'string', default: ''))]
    #[OA\Response(response: 200, description: 'Liste des rubrique manuel')]
    public function getRubriqueManuel(Request $request, LoggerInterface $logger, #[CurrentUser] Session $user){

        $droit = $this->securityRepository->getPermission($user, $logger);
        if ($droit != 23) {
            return $this->json([
                'error' => 1,
                'message' => 'Vous n\'avez pas la permission de voir les rubrique manuel.'
            ], 403);
        }

        try {
            $limit = $request->query->get('limit', 20);
            $pageNumber = $request->query->get('pageNum', 1);
            $q = $request->query->get('q', '');
            $codes = $request->query->get('code','');

            $logger->debug('Récupération des rubriques manuel avec les paramètres',
                [
                    '@Limit' => $limit,
                    '@PageNumber' => $pageNumber,
                    '@Query' => $q,
                    '@Codes' => $codes,
                ]);

            $result = $this->planningRessourceRepository->getRubriqueManuel(
                $limit,
                $pageNumber,
                $q,
                $codes,
                $logger
            );

            return $this->json(['error' => 0, 'data' => $result['data'], 'TotalLignes' => $result['TotalLignes']]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 1,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/verify-code', name: 'app_planning_ressource_verify-code', methods: ['GET'])]
    #[OA\Parameter(name: 'code', in: 'query', description: 'Code à vérifié', schema: new OA\Schema(type: 'string', default: ''))]
    #[OA\Response(response: 200, description: 'Vérification du code de ressource')]
    public function verifyCode(Request $request, LoggerInterface $logger){
        try {
            $code = $request->query->get('code', '');

            $logger->debug('Vérification du code de ressource', ['@Code' => $code]);

            $exists = $this->planningRessourceRepository->verifyCode($code);

            return $this->json(['error' => 0, 'exists' => $exists]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 1,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/{id}', name: 'app_planning_ressource_get', methods: ['GET'])]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Id de la ressource voulu', schema: new OA\Schema(type: 'string', default: ''))]
    #[OA\Response(response: 200, description: 'Ressource')]
    #[IsGranted('RESOURCE_VIEW', subject: 'resource')]
    public function getRessource(int $id){
        try {

            $result = $this->planningRessourceRepository->getRessource($id);

            return $this->json(['error' => 0, 'data' => $result]);
        }catch (\Exception $e) {
            return $this->json([
                'error' => 1,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    #[Route('/manual-events/create', name: 'app_planning_ressource_manuel_create', methods: ['POST'])]
    #[OA\Response(response: 200, description: 'Ressource créée avec succès')]
    #[OA\RequestBody(
        description: 'Les informations de la ressource à créer',
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
    public function createPlaningRessource(Request $request, #[CurrentUser] Session $user, LoggerInterface $logger): JsonResponse
    {
        $droit = $this->securityRepository->getPermission($user, $logger);
        if ($droit != 23) {
            return $this->json([
                'error' => 1,
                'message' => 'Vous n\'avez pas la permission de créer cette ressource.'
            ], 403);
        }

        try {
            $data = json_decode($request->getContent(), true);
            // Validation des données
            if (!isset($data['LibellePlanningRessource']) || !isset($data['CodePlanningRessource'])) {
                return $this->json(['error' => 'Les champs Libelle et Code sont requis'], 400);
            }
            // Appel à la méthode de création dans le repository
            $result = $this->planningRessourceRepository->createRessource($data);
            return $this->json(['error' => 0, 'data' => $result]);
        } catch (\Exception $e) {
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
                new OA\Property(property: 'LibellePlanningRessource', type: 'string'),
                new OA\Property(property: 'CodePlanningRessource', type: 'string'),
                new OA\Property(property: 'Actif', type: 'boolean'),
                new OA\Property(property: 'IdImage', type: 'integer'),
                new OA\Property(property: 'CouleurFondPlanningRessource', type: 'string'),
                new OA\Property(property: 'CouleurBordurePlanningRessource', type: 'string'),
                new OA\Property(property: 'CouleurTextePlanningRessource', type: 'string'),

            ],
            type: 'object'
        )
    )]
    #[OA\Response(response: 200, description: 'Ressource mise à jour avec succès')]
    #[IsGranted('RESOURCE_EDIT', subject: 'resource')]
    public function updatePlaningRessource(int $id, Request $request, LoggerInterface $logger, #[CurrentUser] Session $user){
        try {
            $idPlanning = $request->headers->get('X-Planning-Id');

            if (!$idPlanning) {
                return $this->json(['error' => 1, 'message' => 'Id du planning manquant'], 400);
            }

            $data = json_decode($request->getContent(), true);

            // Appel à la méthode de mise à jour dans le repository
            $result = $this->planningRessourceRepository->updateRessource($id, $data, $logger);

            if ($result['LignesModifiees'] > 0) {

                $this->notifier->notifyPlanningChange(
                    $idPlanning,
                    'RESSOURCE_UPDATED',
                    $user->getIdpersonnel(),
                    $result['data']
                );

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
