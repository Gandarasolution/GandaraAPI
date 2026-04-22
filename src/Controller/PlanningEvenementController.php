<?php

namespace App\Controller;

use App\Repository\PlanningEvenementRepository;
use App\Repository\PlanningRessourceRepository;
use Psr\Log\LoggerInterface;
use \Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;

#[Route('/api/event')]
#[OA\Tag(name: 'Planning Événements')]
class PlanningEvenementController extends AbstractController
{

    public function __construct(
        private PlanningEvenementRepository $planningEvenementRepository,
        private PlanningRessourceRepository $planningRessourceRepository,
        //private EntityManagerInterface $entityManager,
    ){}

    /**
     * Liste les événements entre deux dates.
     *
     * @param \DateTimeInterface $dateStart Date de début de recherche (format date depuis l'URL)
     * @param \DateTimeInterface $dateEnd Date de fin de recherche (format date depuis l'URL)
     * @return JsonResponse JSON contenant la liste des événements: { "error": 0, "data": [...] }
     */
    //GET /api/event- Lister (avec filtres startDate/endDate
    #[Route('/{dateStart}/{dateEnd}', name: 'api_evenements_index', methods: ['GET'])]
    #[OA\Parameter(name: 'dateStart', in: 'path', description: 'Date de début (ex: 2024-01-01)', schema: new OA\Schema(type: 'string', format: 'date'))]
    #[OA\Parameter(name: 'dateEnd', in: 'path', description: 'Date de fin (ex: 2024-12-31)', schema: new OA\Schema(type: 'string', format: 'date'))]
    #[OA\Response(response: 200, description: 'Liste des événements correspondants')]
    public function index(\DateTimeInterface $dateStart, \DateTimeInterface $dateEnd): JsonResponse
    {

        try{
            $result = $this->planningEvenementRepository->findEventsByDate($dateStart, $dateEnd);
            return $this->json(['error' => 0, 'data' => $result]);

        }catch(\Exception $e){
            return $this->json(['error' => 1, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Récupère un événement spécifique par son identifiant.
     *
     * @param int $id Identifiant de l'événement
     * @return JsonResponse JSON avec les détails de l'événement: { "error": 0, "data": {...} } ou erreur
     */
    //GET /api/event/:id- Récupérer un RDV
    #[Route('/{id}', name: 'api_evenements_show', methods: ['GET'])]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Identifiant numérique de l\'événement', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Détails de l\'événement')]
    #[OA\Response(response: 404, description: 'Événement non trouvé')]
    public function show(int $id): JsonResponse
    {
        try{
            $result = $this->planningEvenementRepository->findEventById($id);
            if (!$result) {
                return $this->json(['error' => 1, 'message' => 'Événement non trouvé'], 404);
            }
            return $this->json(['error' => 0, 'data' => $result]);

        }catch(\Exception $e){
            return $this->json(['error' => 1, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Récupère les événements pour un employé spécifique.
     * Attend les paramètres de requête: ?employee={id}&type={Salarie|Interim}
     *
     * @param Request $request La requête HTTP contenant les paramètres.
     * @return JsonResponse JSON avec la liste des événements de l'employé: { "error": 0, "data": [...] } ou erreur
     */
    //GET /api/event?employee=:id&type=Salarie- RDV par employé
    #[Route('/', name: 'api_evenements_by_employee', methods: ['GET'])]
    #[OA\Parameter(name: 'employee', in: 'query', description: 'ID de l\'employé (Salarié ou Intérimaire)', schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'type', in: 'query', description: 'Salarie ou Interim', schema: new OA\Schema(type: 'string', enum: ['Salarie', 'Interim']))]
    #[OA\Response(response: 200, description: 'Liste des événements liés à cet employé')]
    public function getEventsByEmployee(Request $request): JsonResponse
    {
        try {
            $employeeId =$request->query->get('employee');
            $type = $request->query->get('type');

            if (!$type || !in_array($type, ['Salarie', 'Interim'])) {
                return $this->json(['error' => 1, 'message' => 'Le paramètre ?type=Salarie ou ?type=Interim est obligatoire'], 400);
            }

            if (!$employeeId) {
                return $this->json(['error' => 1, 'message' => 'Le paramètre ?employee=:id est obligatoire'], 400);
            }

            $result = $this->planningEvenementRepository->findEventsByEmployee($employeeId, $type);
            return $this->json(['error' => 0, 'data' => $result]);
        } catch (\Exception $e) {
            return $this->json(['error' => 1, 'message' => $e->getMessage()], 500);
        }
    }


    /**
     * Crée un nouvel événement.
     * Données d'entrée (JSON):
     * {
     *   "DebutPlanningEvenement": int (timestamp),
     *   "FinPlanningEvenement": int (timestamp),
     *   "IdPlanningRessource": int,
     *   ...
     * }
     *
     * @param Request $request
     * @param LoggerInterface $logger
     * @return JsonResponse JSON contenant le résultat de la création: { "error": 0, "data": {...} }
     */
    //POST /api/event- Créer un RDV
    #[Route('', name: 'api_evenements_create', methods: ['POST'])]
    #[OA\RequestBody(
        description: 'L\'objet événement JSON (avec timestamp long ms pour Debut/Fin)',
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'DebutPlanningEvenement', type: 'integer', description: 'Timestamp en millisecondes'),
                new OA\Property(property: 'FinPlanningEvenement', type: 'integer', description: 'Timestamp en millisecondes'),
                new OA\Property(property: 'IdPlanningRessource', type: 'integer')
            ],
            type: 'object'
        )
    )]
    #[OA\Response(response: 201, description: 'L\'événement a été créé avec succès')]
    public function create(Request $request, LoggerInterface $logger): JsonResponse
    {
        try {
            $data = $request->toArray();
            // Validation des données d'entrée (simplifiée)
            if (
                empty($data['DebutPlanningEvenement'])
                || empty($data['FinPlanningEvenement'])
                || empty($data['IdPlanningRessource'])
            ) {
                return $this->json(['error' => 1, 'message' => 'Les champs DebutPlanningEvenement, FinPlanningEvenement, IdPlanningRessource  sont obligatoires.'], 400);
            }

            $result = $this->planningEvenementRepository->createEvent($data, $logger);

            return $this->json(['error' => 0, 'data' => $result], 201);

        } catch (\Exception $e) {
            return $this->json(['error' => 1,'message' => 'Erreur lors de la création de l\'événement: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Modifie un événement existant.
     * Données d'entrée (JSON):
     * {
     *   "PlanningEvenementPriorite": int (optionnel),
     *   ...autres champs à mettre à jour...
     * }
     *
     * @param int $id Identifiant de l'événement
     * @param Request $request
     * @return JsonResponse JSON indiquant le succès de l'opération: { "error": 0, "message": "..." } ou erreur
     */
    //PUT /api/event/:id- Modifier un RDV
    #[Route('/{id}', name: 'api_evenements_update', methods: ['PUT'])]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Identifiant de l\'événement à modifier', schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(
        description: 'Champs de l\'événement à modifier',
        required: true,
        content: new OA\JsonContent(type: 'object')
    )]
    #[OA\Response(response: 201, description: 'Événement mis à jour avec succès')]
    public function update(int $id, Request $request): JsonResponse
     {
         try {
             $data = $request->toArray();
             if (($data === null) || $data === []) {
                 return $this->json(['error' => 'Données JSON invalides.'], 400);
             }
             if($data['PlanningEvenementPriorite'] === null){
                 $data['PlanningEvenementPriorite']= 0;
             }

             $lignesModifiees = $this->planningEvenementRepository->updateEvent($id, $data);

             if ($lignesModifiees === 0) {
                 // Pas d'erreur technique, mais l'ID n'existait pas
                 return $this->json(['error' => 1, 'message' => 'Événement introuvable.'], 404);
             }

             return $this->json(['error' => 0, 'message' => 'Événement mis à jour avec succès'], 201);

         } catch (\Exception $e) {
             return $this->json(['error' => 1, 'message' => 'Erreur lors de la mise à jour de l\'événement: ' . $e->getMessage()], 500);
         }
     }


    /**
     * Supprime un événement par son identifiant.
     *
     * @param int $id Identifiant de l'événement
     * @return JsonResponse JSON indiquant le succès: { "error": 0, "message": "..." } ou erreur
     */
    //DELETE /api/event/:id- Supprimer un RDV
    #[Route('/{id}', name: 'api_evenements_delete', methods: ['DELETE'])]
    #[OA\Parameter(name: 'id', in: 'path', description: 'ID de l\'événement à supprimer', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Événement supprimé')]
    public function delete(int $id): JsonResponse
    {
        try {
            $lignesSupprimees = $this->planningEvenementRepository->deleteEvent($id);
            if ($lignesSupprimees === 0) {
                return $this->json(['error' => 1 , 'message' => 'Événement introuvable.'], 404);
            }
            return $this->json(['error' => 0, 'message' => 'Événement supprimé avec succès']);

        } catch (\Exception $e) {
            return $this->json(['error' => 1, 'message' => 'Erreur lors de la suppression de l\'événement: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Met à jour un événement et sa ressource associée.
     * Données d'entrée (JSON):
     * {
     *   "DebutPlanningEvenement": int (timestamp optionnel),
     *   "FinPlanningEvenement": int (timestamp optionnel),
     *   "PlanningEvenementPriorite": int (optionnel),
     *   "IdPlanningRessource": int (optionnel),
     *   "Ressource": {
     *     "IdPlanningRessource": int (optionnel, pris en compte si paramètre précédent absent),
     *     ...autres données de la ressource...
     *   }
     * }
     *
     * @param int $id Identifiant de l'événement
     * @param Request $request
     * @param LoggerInterface $logger
     * @return JsonResponse JSON indiquant le succès de l'opération: { "error": 0, "message": "..." }
     */
    // PUT /api/event/updateRessourceAndEvent/:id -> met à jour un événement et la ressource associée via les procédure stockée
    #[Route('/updateRessourceAndEvent/{id}', name: 'api_evenement_et_ressource_update', methods: ['PUT'])]
    #[OA\Tag(name: 'Opérations complexes événement')]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Identifiant de l\'événement ciblé', schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(
        description: 'Données combinées de l\'événement et de la ressource liée',
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'DebutPlanningEvenement', type: 'integer'),
                new OA\Property(property: 'Ressource', type: 'object')
            ],
            type: 'object'
        )
    )]
    #[OA\Response(response: 200, description: 'Événement et ressource mis à jour')]
    public function updateWithProcedure(int $id, Request $request, LoggerInterface $logger): JsonResponse
    {
        try {
            $data = $request->toArray();

            if (($data === null) || $data === []) {
                return $this->json(['error' => 1, 'message' => 'Données JSON invalides.'], 400);
            }

            // Normalisation des timestamps envoyés en millisecondes -> int
            if (isset($data['DebutPlanningEvenement']) && is_numeric($data['DebutPlanningEvenement'])) {
                $data['DebutPlanningEvenement'] = (int) $data['DebutPlanningEvenement'];
            }
            if (isset($data['FinPlanningEvenement']) && is_numeric($data['FinPlanningEvenement'])) {
                $data['FinPlanningEvenement'] = (int) $data['FinPlanningEvenement'];
            }

            if ($data['PlanningEvenementPriorite'] === null) {
                $data['PlanningEvenementPriorite'] = 0;
            }



            $logger->info('Appel PS update pour événement ' . $id, ['payload' => $data]);

            $lignesModifiees = $this->planningEvenementRepository->updateEvent($id, $data);

            if ($lignesModifiees === 0) {
                return $this->json(['error' => 1, 'message' => 'Événement introuvable ou aucune modification effectuée.'], 404);
            }

            // Si le payload contient des données de ressource, tenter de mettre à jour la ressource associée
            $ressourceId = $data['IdPlanningRessource'] ?? ($data['Ressource']['IdPlanningRessource'] ?? null);
            if ($ressourceId !== null && isset($data['Ressource']) && is_array($data['Ressource'])) {
                $lignesModifiees = $this->planningRessourceRepository->updateRessource((int)$ressourceId, $data['Ressource']);

                if ($lignesModifiees === 0){
                    return $this->json(['error' => 1, 'message' => 'Ressource introuvable ou aucune modification effectuée.'], 404);
                }
            }

            return $this->json([
                'error' => 0,
                'message' => 'Événement mis à jour avec succès',
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => 1, 'message' => 'Erreur lors de la mise à jour via PS: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Divise un événement en deux à une date précise.
     * Données d'entrée (JSON):
     * {
     *   "DateCoupure": int (timestamp où diviser l'événement)
     * }
     *
     * @param int $id Identifiant de l'événement
     * @param Request $request
     * @return JsonResponse JSON contenant les données de l'événement divisé: { "error": 0, "data": {...} }
     */
    #[Route('/divide/{id}', name: 'api_evenement_diviser', methods: ['PUT'])]
    #[OA\Tag(name: 'Opérations complexes événement')]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Identifiant de l\'événement à diviser', schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(
        description: 'Timestamp de la coupure de l\'évènement',
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'DateCoupure', type: 'integer', description: 'Timestamp précis de séparation')
            ],
            type: 'object'
        )
    )]
    #[OA\Response(response: 200, description: 'L\'événement a été divisé, retour des nouvelles données')]
    public function divideEvent(int $id, Request $request): JsonResponse
    {
        try {
            $data = $request->toArray();

            if (($data === null) || $data === []) {
                return $this->json(['error' => 1, 'message' => 'Données JSON invalides.'], 400);
            }

            // Normalisation des timestamps envoyés en millisecondes -> int
            if (isset($data['DateCoupure']) && is_numeric($data['DateCoupure'])) {
                $data['DateCoupure'] = (int) $data['DateCoupure'];
            }


            $result = $this->planningEvenementRepository->divideEvent($id, $data);

            return $this->json(['error' => 0, 'data' => $result], 200);

        } catch (\Exception $e) {
            return $this->json(['error' => 1, 'message' => 'Erreur lors de la division de l\'événement: ' . $e->getMessage()], 500);
        }
     }

    /**
     * Répète un événement existant.
     * Données d'entrée (JSON): Doit contenir les paramètres requis par la méthode repeatEvent du repository.
     *
     * @param Request $request
     * @return JsonResponse JSON avec le résultat de la répétition: { "error": 0, "data": {...} }
     */
    #[Route('/repeat', name: 'api_evenement_repeat', methods: ['POST'])]
    #[OA\Tag(name: 'Opérations complexes événement')]
    #[OA\RequestBody(
        description: 'Paramètres logiques pour la répétition de l\'événement',
        required: true,
        content: new OA\JsonContent(type: 'object')
    )]
    #[OA\Response(response: 201, description: 'Répétition effectuée avec succès')]
    public function repeatEvent(Request $request): JsonResponse
    {
        try {
            $data = $request->toArray();

            if (!isset($data['Date']) || !is_array($data['Date'])) {
                return new JsonResponse(['error' => 'Tableau de dates manquant'], 400);
            }


            $result = $this->planningEvenementRepository->repeatEvent($data);

            return $this->json(['error' => 0, 'data' => $result], 201);

        } catch (\Exception $e) {
            return $this->json(['error' => 1, 'message' => 'Erreur lors de la répétition de l\'événement: ' . $e->getMessage()], 500);
        }
    }
}
