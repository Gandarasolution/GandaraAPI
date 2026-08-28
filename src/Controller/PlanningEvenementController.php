<?php

namespace App\Controller;

use App\Entity\Planningevenement;
use App\Repository\PlanningEvenementRepository;
use App\Repository\PlanningRessourceRepository;
use App\Service\MercureNotificationService;
use App\Entity\Session;
use Doctrine\DBAL\Exception;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[Route('/api/event')]
#[OA\Tag(name: 'Planning Événements')]
class PlanningEvenementController extends AbstractController
{

    public function __construct(
        private readonly MercureNotificationService $notifier,
        private readonly PlanningEvenementRepository         $planningEvenementRepository,
        private readonly PlanningRessourceRepository         $planningRessourceRepository,
        private readonly CacheInterface                      $cache,
        //private EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
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
    #[IsGranted('VIEW_ALL', message: 'Vous n\'avez pas la permission de récupérer tous les événements.')]
    public function index(\DateTimeInterface $dateStart, \DateTimeInterface $dateEnd, Request $request): JsonResponse
    {
        $startTime = microtime(true);

        $idPlanning = $request->headers->get('X-Planning-Id');

        if (!$idPlanning) {
            return new JsonResponse(['error' => 1, 'message' => 'Id du planning manquant'], 400);
        }

        $idPlanningVue = $request->headers->get('X-PlanningVue-Id');

        if (!$idPlanningVue) {
            return new JsonResponse(['error' => 1, 'message' => 'Id de la vue du planning manquante'], 400);
        }

        try{
            $result = $this->planningEvenementRepository->findEventsByDate($dateStart, $dateEnd, $idPlanning, $idPlanningVue);

            $endTime = microtime(true);

            // 3. On calcule la différence
            $executionTime = $endTime - $startTime;

            // 4. On log le résultat (en secondes avec les millisecondes après la virgule)
            $this->logger->info(sprintf('Temps de traitement de la route : %.4f secondes', $executionTime));

            return new JsonResponse(['error' => 0, 'data' => $result]);

        }catch(\Exception $e){
            return new JsonResponse(['error' => 1, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Récupère un événement spécifique par son identifiant.
     *
     * @param int $id Identifiant de l'événement
     * @return JsonResponse JSON avec les détails de l'événement: { "error": 0, "data": {...} } ou erreur
     * @throws InvalidArgumentException
     */
    //GET /api/event/:id- Récupérer un RDV
    #[Route('/{id}', name: 'api_evenements_show', methods: ['GET'])]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Identifiant numérique de l\'événement', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Détails de l\'événement')]
    #[OA\Response(response: 404, description: 'Événement non trouvé')]
    #[OA\Response(response: 409, description: 'Événement verrouillé par un autre utilisateur')]
    public function show(int $id, LoggerInterface $logger, #[CurrentUser] Session $user, Request $request): JsonResponse
    {

        $idPlanning = $request->headers->get('X-Planning-Id');

        if (!$idPlanning) {
            return new JsonResponse(['error' => 1, 'message' => 'Id du planning manquant'], 400);
        }

        $idPlanningVue = $request->headers->get('X-PlanningVue-Id');

        if (!$idPlanningVue) {
            return new JsonResponse(['error' => 1, 'message' => 'Id de la vue du planning manquante'], 400);
        }

        $cacheKey = 'edit_rdv_' . $idPlanning . '_' . $id;
        $idUser = $user->getIdpersonnel();

        try{

            $lockOwnerId = $this->cache->get($cacheKey, function (ItemInterface $item) use ($idUser) {
                // Si la clé n'existait pas (RDV libre), ce bloc s'exécute.
                // On fixe la durée de vie du verrou (ex: 2 minutes)
                $item->expiresAfter(120);

                // On retourne l'ID de l'utilisateur actuel.
                // C'est cette valeur qui sera sauvegardée dans Redis !
                return $idUser;
            });

            if ($lockOwnerId !== $idUser) {
                // Le verrou appartient à quelqu'un d'autre ! (ex: $lockOwnerId = 45, et toi = 12)
                return new JsonResponse([
                    'error' => 409,
                    'isLocked' => true,
                    'message' => 'Ce rendez-vous est actuellement en cours d\'édition.'
                ]);
            }

            $this->notifier->notifyPlanningChange(
                $idPlanning,
                'APPOINTMENT_LOCKED',
                $idUser,
                ['IdPlanningEvenement' => $id]
            );

            $result = $this->planningEvenementRepository->findEventById($id, $logger, $idPlanning, $idPlanningVue);
            if (!$result) {
                $this->notifier->notifyPlanningChange(
                    $idPlanning,
                    'APPOINTMENT_UNLOCKED',
                    $idUser,
                    ['IdPlanningEvenement' => $id]
                );
                return new JsonResponse(['error' => 1, 'message' => 'Événement non trouvé'], 404);
            }



            return new JsonResponse(['error' => 0, 'data' => $result]);

        }catch(\Exception $e){
            return new JsonResponse(['error' => 1, 'message' => $e->getMessage()], 500);
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
        $idPlanning = $request->headers->get('X-Planning-Id');

        if (!$idPlanning) {
            return new JsonResponse(['error' => 1, 'message' => 'Id du planning manquant'], 400);
        }

        $idPlanningVue = $request->headers->get('X-PlanningVue-Id');

        if (!$idPlanningVue) {
            return new JsonResponse(['error' => 1, 'message' => 'Id de la vue du planning manquante'], 400);
        }

        try {
            $employeeId =$request->query->get('employee');
            $type = $request->query->get('type');

            if (!$type || !in_array($type, ['Salarie', 'Interim'])) {
                return new JsonResponse(['error' => 1, 'message' => 'Le paramètre ?type=Salarie ou ?type=Interim est obligatoire'], 400);
            }

            if (!$employeeId) {
                return new JsonResponse(['error' => 1, 'message' => 'Le paramètre ?employee=:id est obligatoire'], 400);
            }

            $result = $this->planningEvenementRepository->findEventsByEmployee($employeeId, $type, $idPlanning, $idPlanningVue);
            return new JsonResponse(['error' => 0, 'data' => $result]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 1, 'message' => $e->getMessage()], 500);
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
    public function create(Request $request, LoggerInterface $logger, #[CurrentUser] ?Session $user): JsonResponse
    {
        $data = $request->toArray();

        try {
            $idRessource = $data['IdPlanningRessource'] ?? null;
            if (!$idRessource) {
                return new JsonResponse(['error' => 1, 'message' => 'Le champ IdPlanningRessource est obligatoire.'], 400);
            }

            // 3. On passe la ressource au Voter !
            $this->denyAccessUnlessGranted('CREATE_EVENEMENT', $idRessource);

            $idPlanning = $request->headers->get('X-Planning-Id');

            if (!$idPlanning) {
                return new JsonResponse(['error' => 1, 'message' => 'Id du planning manquant'], 400);
            }

            if (!$user) {
                return new JsonResponse(['error' => 1, 'message' => 'Utilisateur non authentifié.'], 401);
            }

            // Validation des données d'entrée (simplifiée)
            if (
                empty($data['DebutPlanningEvenement'])
                || empty($data['FinPlanningEvenement'])
            ) {
                return new JsonResponse(['error' => 1, 'message' => 'Les champs DebutPlanningEvenement, FinPlanningEvenement sont obligatoires.'], 400);
            }

            $result = $this->planningEvenementRepository->createEvent($data, $logger, $idPlanning);

            $this->notifier->notifyPlanningChange(
                $idPlanning,
                'APPOINTMENT_CREATED',
                $user->getIdPersonnel(),
                $result
            );

            return new JsonResponse(['error' => 0, 'data' => $result], 201);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 1,'message' => 'Erreur lors de la création de l\'événement: ' . $e->getMessage()], 500);
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
     * @throws InvalidArgumentException
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
    #[IsGranted('UPDATE_EVENEMENT',  subject: 'evenement', message: 'Vous n\'avez pas la permission de modifier cet événement.')]
    public function update(Planningevenement $evenement, Request $request, #[CurrentUser] Session $user, LoggerInterface $logger): JsonResponse
     {
         $id = $evenement->getIdplanningevenement();
         $idPlanning = $request->headers->get('X-Planning-Id');

         if (!$idPlanning) {
             return new JsonResponse(['error' => 1, 'message' => 'Id du planning manquant'], 400);
         }
         $cacheKey = 'edit_rdv_' . $idPlanning . '_' . $id;

         $idUser = $user->getIdpersonnel();

         try {
             $cacheItem = $this->cache->getItem($cacheKey);

             if ($cacheItem->isHit()) {
                 $ownerId = $cacheItem->get();

                 if($ownerId !== $idUser) {
                     return new JsonResponse([
                         'error' => 409,
                         'isLocked' => true,
                         'message' => 'Ce rendez-vous est actuellement en cours d\'édition.'
                     ]);
                 }
             }


             $data = $request->toArray();
             if (($data === null) || $data === []) {
                 return new JsonResponse(['error' => 'Données JSON invalides.'], 400);
             }
             if($data['PlanningEvenementPriorite'] === null){
                 $data['PlanningEvenementPriorite']= 0;
             }

             $logger->debug('Appel de la mise à jour de l\'événement', ['payload' => $data]);

             $result = $this->planningEvenementRepository->updateEvent($id, $data);

             $logger->debug('Résultat de la mise à jour de l\'événement', ['result' => $result]);
             if ($result['LignesModifiees'] === 0) {
                 // Pas d'erreur technique, mais l'ID n'existait pas
                 return new JsonResponse(['error' => 1, 'message' => 'Événement introuvable.'], 404);
             }

             $cacheKey = 'edit_rdv_' . $idPlanning . '_' . $id;

             // La fonction delete() supprime instantanément la clé de Redis
             $this->cache->delete($cacheKey);

             $result['data']['isLocked'] = false;

             $this->notifier->notifyPlanningChange(
                 $idPlanning,
                 'APPOINTMENT_UPDATED',
                 $user->getIdPersonnel(),
                 $result['data']
             );

             return new JsonResponse(['error' => 0, 'message' => 'Événement mis à jour avec succès'], 201);

         } catch (\Exception $e) {
             return new JsonResponse(['error' => 1, 'message' => 'Erreur lors de la mise à jour de l\'événement: ' . $e->getMessage()], 500);
         }
     }


    /**
     * Supprime un événement par son identifiant.
     *
     * @param int $id Identifiant de l'événement
     * @return JsonResponse JSON indiquant le succès: { "error": 0, "message": "..." } ou erreur
     */
    //DELETE /api/event/:id- Supprimer un RDV
    #[Route('/{id}', name: 'api_evenement_delete', methods: ['DELETE'])]
    #[OA\Parameter(name: 'id', in: 'path', description: 'ID de l\'événement à supprimer', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Événement supprimé')]
    #[IsGranted('DELETE_EVENEMENT', subject: 'evenement',  message: 'Vous n\'avez pas la permission de supprimer cet événement.')]
    public function delete(Planningevenement $evenement, Request $request, LoggerInterface $logger, #[CurrentUser] ?Session $user): JsonResponse
    {
        try {
            $id = $evenement->getIdplanningevenement();
            $idPlanning = $request->headers->get('X-Planning-Id');

            if (!$idPlanning) {
                return new JsonResponse(['error' => 1, 'message' => 'Id du planning manquant'], 400);
            }

            $lignesSupprimees = $this->planningEvenementRepository->deleteEvent($id);
            if ($lignesSupprimees === 0) {
                return new JsonResponse(['error' => 1 , 'message' => 'Événement introuvable.'], 404);
            }

            $this->notifier->notifyPlanningChange(
                $idPlanning,
                'APPOINTMENT_DELETED',
                $user->getIdPersonnel(),
                ['IdPlanningEvenement' => $id]
            );

            return new JsonResponse(['error' => 0, 'message' => 'Événement supprimé avec succès']);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 1, 'message' => 'Erreur lors de la suppression de l\'événement: ' . $e->getMessage()], 500);
        }
    }


    /**
     * Supprime des événements par identifiants.
     *
     * @param Request $request La requête HTTP contenant un tableau d'IDs à supprimer: { "ids": [1, 2, 3] }
     * @return JsonResponse JSON indiquant le succès: { "error": 0, "message": "..." } ou erreur
     */
    #[Route('', name: 'api_evenements_delete', methods: ['DELETE'])]
    #[OA\Response(response: 200, description: 'Événement supprimé')]
    public function deletes(Request $request, LoggerInterface $logger, #[CurrentUser] ?Session $user): JsonResponse
    {
        try {

            $data = $request->toArray();
            if (!isset($data['ids']) || !is_array($data['ids']) || empty($data['ids'])) {
                return new JsonResponse(['error' => 1, 'message' => 'Le champ "ids" doit être un tableau d\'identifiants non vide.'], 400);
            }
            $this->denyAccessUnlessGranted('MASS_DELETE_EVENEMENT', $data['ids']);

            $idPlanning = $request->headers->get('X-Planning-Id');

            if (!$idPlanning) {
                return new JsonResponse(['error' => 1, 'message' => 'Id du planning manquant'], 400);
            }

            $lignesSupprimees = $this->planningEvenementRepository->deleteEvents($data);
            if ($lignesSupprimees === 0) {
                return new JsonResponse(['error' => 1 , 'message' => 'Événement introuvable.'], 404);
            }

            $this->notifier->notifyPlanningChange(
                $idPlanning,
                'APPOINTMENTS_DELETED',
                $user->getIdPersonnel(),
                ['deletedIds' => $data['ids']]
            );

            return new JsonResponse(['error' => 0, 'message' => 'Événement supprimé avec succès']);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 1, 'message' => 'Erreur lors de la suppression de l\'événement: ' . $e->getMessage()], 500);
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
    #[IsGranted('UPDATE_EVENEMENT', subject: 'evenement', message: 'Vous n\'avez pas la permission de modifier cet événement.')]
    public function updateWithProcedure(Planningevenement $evenement, Request $request, LoggerInterface $logger, #[CurrentUser] ?Session $user): JsonResponse
    {
        $id = $evenement->getIdplanningevenement();
        try {
            $idPlanning = $request->headers->get('X-Planning-Id');

            if (!$idPlanning) {
                return new JsonResponse(['error' => 1, 'message' => 'Id du planning manquant'], 400);
            }

            $data = $request->toArray();

            if (($data === null) || $data === []) {
                return new JsonResponse(['error' => 1, 'message' => 'Données JSON invalides.'], 400);
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

            $returnData = [];

            $result = $this->planningEvenementRepository->updateEvent($id, $data);

            $logger->debug('Résultat de la mise à jour de l\'événement via PS', ['result' => $result]);

            if ($result['LignesModifiees'] === 0) {
                return new JsonResponse(['error' => 1, 'message' => 'Événement introuvable ou aucune modification effectuée.'], 404);
            }

            $returnData['appointment'] = $result['data'];

            // Si le payload contient des données de ressource, tenter de mettre à jour la ressource associée
            $ressourceId = $data['IdPlanningRessource'] ?? ($data['Ressource']['IdPlanningRessource'] ?? null);
            if ($ressourceId !== null && isset($data['Ressource']) && is_array($data['Ressource'])) {
                $result = $this->planningRessourceRepository->updateRessource((int)$ressourceId, $data['Ressource'], $logger);

                if ($result['LignesModifiees'] === 0){
                    return new JsonResponse(['error' => 1, 'message' => 'Ressource introuvable ou aucune modification effectuée.'], 404);
                }
            }
            $logger->debug('Résultat de la mise à jour de la ressource via PS', ['result' => $result]);
            $returnData['ressources'] = $result['data'];

            $cacheKey = 'edit_rdv_' . $idPlanning . '_' . $id;
            // La fonction delete() supprime instantanément la clé de Redis
            $this->cache->delete($cacheKey);

            $this->notifier->notifyPlanningChange(
                $idPlanning,
                'APPOINTMENT_AND_RESSOURCE_UPDATED',
                $user->getIdPersonnel(),
                $returnData
            );

            return new JsonResponse([
                'error' => 0,
                'message' => 'Événement mis à jour avec succès',
            ]);

        } catch (\Exception $e) {

            return new JsonResponse(['error' => 1, 'message' => 'Erreur lors de la mise à jour via PS: ' . $e->getMessage()], 500);
        } catch (InvalidArgumentException $e) {
            return new JsonResponse(['error' => 1, 'message' => 'Erreur de cache: ' . $e->getMessage()], 500);
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
    #[IsGranted('UPDATE_EVENEMENT',  subject: 'evenement', message: 'Vous n\'avez pas la permission de modifier cet événement.')]
    public function divideEvent(Planningevenement $evenement, Request $request, LoggerInterface $logger, #[CurrentUser] ?Session $user): JsonResponse
    {
        try {
            $id = $evenement->getIdplanningevenement();
            $idPlanning = $request->headers->get('X-Planning-Id');

            if (!$idPlanning) {
                return new JsonResponse(['error' => 1, 'message' => 'Id du planning manquant'], 400);
            }

            $data = $request->toArray();

            if (($data === null) || $data === []) {
                return new JsonResponse(['error' => 1, 'message' => 'Données JSON invalides.'], 400);
            }

            // Normalisation des timestamps envoyés en millisecondes -> int
            if (isset($data['DateCoupure']) && is_numeric($data['DateCoupure'])) {
                $data['DateCoupure'] = (int) $data['DateCoupure'];
            }

            $result = $this->planningEvenementRepository->divideEvent($id, $data, $logger);

            $cacheKey = 'edit_rdv_' . $idPlanning . '_' . $id;
            $this->cache->delete($cacheKey);

            $this->notifier->notifyPlanningChange(
                $idPlanning,
                'APPOINTMENT_DIVISION_UPDATED',
                $user->getIdPersonnel(),
                [
                    'originalEventId' => $id,
                    'newEvent' => $result,
                    'divisionDate' => $data['DateCoupure']
                ]
            );

            return new JsonResponse(['error' => 0, 'data' => ['NouvelIdEvenement' => $result['IdPlanningEvenement']]], 200);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 1, 'message' => 'Erreur lors de la division de l\'événement: ' . $e->getMessage()], 500);
        } catch (InvalidArgumentException $e) {
            return new JsonResponse(['error' => 1, 'message' => 'Erreur de cache: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Répète un événement existant.
     * Données d'entrée (JSON): Doit contenir les paramètres requis par la méthode repeatEvent du repository.
     *
     * @param Request $request
     * @return JsonResponse JSON avec le résultat de la répétition: { "error": 0, "data": {...} }
     * @throws Exception
     */
    #[Route('/repeat/{id}', name: 'api_evenement_repeat', methods: ['POST'])]
    #[OA\Tag(name: 'Opérations complexes événement')]
    #[OA\RequestBody(
        description: 'Paramètres logiques pour la répétition de l\'événement',
        required: true,
        content: new OA\JsonContent(type: 'object')
    )]
    #[OA\Response(response: 201, description: 'Répétition effectuée avec succès')]
    public function repeatEvent(int $id, Request $request, LoggerInterface $logger, #[CurrentUser] ?Session $user): JsonResponse
    {
        $data = $request->toArray();
        try {
            $idRessource = (int)$data['IdPlanningRessource'] ?? null;
            if (!$idRessource) {
                return new JsonResponse(['error' => 1, 'message' => 'Le champ IdPlanningRessource est obligatoire.'], 400);
            }

            // 3. On passe la ressource au Voter !
            $this->denyAccessUnlessGranted('REPEAT_EVENEMENT', $idRessource);


            $idPlanning = $request->headers->get('X-Planning-Id');

            if (!$idPlanning) {
                return new JsonResponse(['error' => 1, 'message' => 'Id du planning manquant'], 400);
            }

            $data = $request->toArray();

            if (!isset($data['Date']) || !is_array($data['Date'])) {
                return new JsonResponse(['error' => 'Tableau de dates manquant'], 400);
            }


            $result = $this->planningEvenementRepository->repeatEvent($data, $idPlanning);

            $cacheKey = 'edit_rdv_' . $idPlanning . '_' . $id;
            $this->cache->delete($cacheKey);

            $this->notifier->notifyPlanningChange(
                $idPlanning,
                'APPOINTMENT_REPEATED',
                $user->getIdPersonnel(),
                [
                    'data' => $result['data'],
                    'originalEventId' => $id
                ]

            );

            return new JsonResponse(['error' => 0, 'data' => $result['ids']], 201);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 1, 'message' => 'Erreur lors de la répétition de l\'événement: ' . $e->getMessage()], 500);
        } catch (InvalidArgumentException $e) {
            return new JsonResponse(['error' => 1, 'message' => 'Erreur de cache: ' . $e->getMessage()], 500);
        }
    }


    /**
     * @throws InvalidArgumentException
     */
    #[Route('/{id}/unlock', methods: ['POST'])]
    #[OA\Response(response: 200, description: 'Rendez-vous déverrouillé avec succès')]
    #[OA\Response(response: 500, description: 'Erreur lors du déverrouillage du rendez-vous')]
    public function unlockRdv(int $id, #[CurrentUser] Session $user, Request $request): JsonResponse
    {
        try {
            $idPlanning = $request->headers->get('X-Planning-Id');

            if (!$idPlanning) {
                return new JsonResponse(['error' => 1, 'message' => 'Id du planning manquant'], 400);
            }

            $cacheKey = 'edit_rdv_' . $idPlanning . '_' . $id;

            $cacheItem = $this->cache->getItem($cacheKey);

            if ($cacheItem->isHit()) {
                // Le RDV est dans le cache Redis !
                $ownerId = $cacheItem->get();

                // Est-ce que le propriétaire du verrou est différent de moi ?
                // (Si ownerId === currentUserId, c'est mon verrou, donc ce n'est pas locked pour moi)
                if ($ownerId !== $user->getIdPersonnel()) {
                    return new JsonResponse([
                        'error' => 409,
                        'isLocked' => true,
                        'message' => 'Ce rendez-vous est actuellement en cours d\'édition par un autre utilisateur.'
                    ]);
                }
            }


            // La fonction delete() supprime instantanément la clé de Redis
            $this->cache->delete($cacheKey);

            $this->notifier->notifyPlanningChange(
                $idPlanning,
                'APPOINTMENT_UNLOCKED',
                $user->getIdPersonnel(),
                ['IdPlanningEvenement' => $id]
            );

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 1, 'message' => 'Erreur lors du déverrouillage du rendez-vous: ' . $e->getMessage()], 500);
        }
        return new JsonResponse(['success' => true]);
    }


    #[Route('/{id}/lock-quick', methods: ['POST'])]
    #[IsGranted('EVENEMENT_LOCK', subject: 'evenement', message: 'Vous n\'avez pas la permission de lock cet événement.')]
    public function quickLock(Planningevenement $evenement, #[CurrentUser] Session $user, LoggerInterface $logger, Request $request): JsonResponse
    {
        $idPlanning = $request->headers->get('X-Planning-Id');

        if (!$idPlanning) {
            return new JsonResponse(['error' => 1, 'message' => 'Id du planning manquant'], 400);
        }

        $id = $evenement->getIdplanningevenement();

        $cacheKey = 'edit_rdv_' . $idPlanning . '_' . $id;
        $currentUserId = $user->getIdPersonnel();

        try {
            $ownerId = $this->cache->get($cacheKey, function (ItemInterface $item) use ($currentUserId) {
                // Largement suffisant pour un Drag & Drop. S'il abandonne, ça se libère très vite.
                $item->expiresAfter(20);
                return $currentUserId;
            });
        } catch (InvalidArgumentException $e) {
            $logger->debug('Erreur lors de la récupération du verrou pour l\'événement ' . $id, ['exception' => $e]);
            return new JsonResponse(['error' => 1, 'message' => 'Erreur'], 500);
        }

        if ($ownerId !== $currentUserId) {
            return new JsonResponse(['error' => 409, 'message' => 'Ce rendez-vous est actuellement en cours d\'édition.'], 409);
        }

        $this->notifier->notifyPlanningChange(
            $idPlanning,
            'APPOINTMENT_LOCKED',
            $currentUserId,
            ['IdPlanningEvenement' => $id]
        );

        return new JsonResponse(['error' => 0]);
    }

}
