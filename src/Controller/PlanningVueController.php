<?php

namespace App\Controller;

use App\Entity\Session;
use App\Repository\PlanningTypesJourRepository;
use App\Repository\PlanningVueRepository;
use App\Service\MercureNotificationService;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[Route('/api/planning')]
#[OA\Tag(name: 'Configurations/Vues')]
class PlanningVueController extends AbstractController
{

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly PlanningTypesJourRepository $typesJourRepository,
        private MercureNotificationService $notifier,
        private PlanningVueRepository $planningVueRepository,
        private CacheInterface $cache
    )
    {}




    /**
     * @throws Exception
     */
    #[Route('/getLastVue', name: 'api_last_config_user', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Récupère la dernière vue d\'un utilisateur pour un planning donné')]
    #[OA\Response(response: 400, description: 'Paramètre idPlanning manquant ou invalide')]
    #[OA\Response(response: 500, description: 'Erreur serveur')]
    public function getLastVue(#[CurrentUser] Session $user, Request $request) :JsonResponse{
        $idPlanning = $request->headers->get('X-Planning-Id');

        if ($idPlanning !== null && !is_numeric($idPlanning) || $idPlanning < 0) {
            $this->logger->debug('Le paramètre idPlanning doit être un entier positif. Valeur reçue: {idPlanning}', [
                'idPlanning' => $idPlanning,
            ]);
            throw new BadRequestHttpException('Le paramètre idPlanning doit être un entier positif.');
        }

        $result = $this->planningVueRepository->getLastVue($user, $idPlanning);
        return $this->json(['data' => $result]);

    }

    #[Route('/vue/available-columns', name: 'api_config_available_columns', methods: ['GET'])]
    #[OA\Parameter(name: 'viewType', in: 'query', description: 'Type de vue', schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Récupère les colonnes disponibles pour une vue spécifique')]
    #[IsGranted('VUE_VIEW', message: 'Vous n\'avez pas la permission de visualiser cette vue.')]
    public function getAvailableColumns(Request $request) :JsonResponse {

        $viewType = $request->query->get('viewType');

        $result = $this->planningVueRepository->getAvailableColumns($viewType, $this->logger);

        return $this->json(['data' => $result]);
    }


    #[Route('/chantier/getLastVue', name: 'api_last_config_user_chantier', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Récupère la dernière vue d\'un utilisateur pour un planning donné')]
    #[OA\Response(response: 400, description: 'Paramètre idPlanning manquant ou invalide')]
    #[OA\Response(response: 500, description: 'Erreur serveur')]
    public function getChantierLastVue(#[CurrentUser] Session $user, Request $request) :JsonResponse{
        $idPlanning = $request->headers->get('X-Planning-Id');

        if ($idPlanning !== null && !is_numeric($idPlanning) || $idPlanning < 0) {
            $this->logger->debug('Le paramètre idPlanning doit être un entier positif. Valeur reçue: {idPlanning}', [
                'idPlanning' => $idPlanning,
            ]);
            throw new BadRequestHttpException('Le paramètre idPlanning doit être un entier positif.');
        }

        $result = $this->planningVueRepository->getChantierLastVue($user, $idPlanning);
        return $this->json(['data' => $result]);

    }



    /**
     * @throws Exception
     */
    #[Route('/chantier/vue/{id}', name: 'api_config_chantier', methods: ['GET'])]
    #[OA\Parameter(name: 'id', in: 'path', description: 'ID de la vue', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Récupère les détails d\'une vue chantier spécifique')]
    #[IsGranted('VUE_VIEW', message: 'Vous n\'avez pas la permission de visualiser cette vue.')]
    public function getChantierVue(int $id, Request $request, #[CurrentUser] Session $user) :JsonResponse {

        $viewType = $request->query->get('viewType');

        if ($viewType === null){
            throw new BadRequestHttpException('Le paramètre viewType est manquant dans la requête.');
        }

        $result = $this->planningVueRepository->getChantierVue($user, $id, $this->logger, $viewType);

        return $this->json(['data' => $result]);

    }


    /**
     * @throws Exception
     */
    #[Route('/vue/{id}', name: 'api_config', methods: ['GET'])]
    #[OA\Parameter(name: 'id', in: 'path', description: 'ID de la vue', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Récupère les détails d\'une vue spécifique')]
    #[IsGranted('VUE_VIEW', message: 'Vous n\'avez pas la permission de visualiser cette vue.')]
    public function getVue(int $id) :JsonResponse {
        $result = $this->planningVueRepository->getVue($id, $this->logger);

        return $this->json(['data' => $result]);
    }




    /**
     * @throws Exception
     */
    #[Route('/chantier/vue/user/{userId}', name: 'api_configs_chantier_user', methods: ['GET'])]
    #[OA\Parameter(name: 'userId', in: 'path', description: 'ID de l\'utilisateur', schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'idPlanning', in: 'query', description: 'ID de planning', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Liste des configurations de l\'utilisateur pour les chantiers')]
    public function getUserChantierConfigs(int $userId, Request $request, #[CurrentUser] Session $user): JsonResponse
    {
        if ($user->getIdpersonnel() !== $userId) {
            throw new BadRequestHttpException('Vous ne pouvez pas accéder aux configurations d\'un autre utilisateur.');
        }

        $idPlanning = $request->headers->get('X-Planning-Id');

        if ($idPlanning === null) {
            throw new BadRequestHttpException('Le header X-Planning-Id est manquant.');
        }

        if ($idPlanning !== null && !is_numeric($idPlanning) || $idPlanning < 0) {
            $this->logger->debug('Le paramètre idPlanning doit être un entier positif. Valeur reçue: {idPlanning}', [
                'idPlanning' => $idPlanning,
            ]);
            throw new BadRequestHttpException('Le paramètre idPlanning doit être un entier positif.');
        }

        $configs = $this->planningVueRepository->getChantierConfigUser($userId, $idPlanning, $this->logger);
        return $this->json(['data' => $configs]);

    }

    /**
     * @throws Exception
     */
    #[Route('/vue/user/{userId}', name: 'api_configs_user', methods: ['GET'])]
    #[OA\Parameter(name: 'userId', in: 'path', description: 'ID de l\'utilisateur', schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'idPlanning', in: 'query', description: 'ID de planning', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Liste des configurations de l\'utilisateur')]
    public function getUserConfigs(int $userId, Request $request, #[CurrentUser] Session $user): JsonResponse
    {
        if ($user->getIdpersonnel() !== $userId) {
            throw new BadRequestHttpException('Vous ne pouvez pas accéder aux configurations d\'un autre utilisateur.');
        }

        $idPlanning = $request->headers->get('X-Planning-Id');

        if ($idPlanning === null) {
            throw new BadRequestHttpException('Le header X-Planning-Id est manquant.');
        }

        if ($idPlanning !== null && !is_numeric($idPlanning) || $idPlanning < 0) {
            $this->logger->debug('Le paramètre idPlanning doit être un entier positif. Valeur reçue: {idPlanning}', [
                'idPlanning' => $idPlanning,
            ]);
            throw new BadRequestHttpException('Le paramètre idPlanning doit être un entier positif.');
        }

        $configs = $this->planningVueRepository->getConfigUser($userId, $idPlanning);
        return $this->json(['data' => $configs]);

    }

    #[Route('/getLastPosition', name: 'lastPositionUser', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Récupère la dernière position d\'un utilisateur')]
    public function getLastPositionUser(#[CurrentUser] Session $user, Request $request): JsonResponse
    {
        $idPlanning = $request->headers->get('X-Planning-Id');

        if ($idPlanning !== null && !is_numeric($idPlanning) || $idPlanning < 0) {
            $this->logger->debug('Le paramètre idPlanning doit être un entier positif. Valeur reçue: {idPlanning}', [
                'idPlanning' => $idPlanning,
            ]);
            throw new BadRequestHttpException('Le paramètre idPlanning doit être un entier positif.');
        }

        $result = $this->planningVueRepository->getLastPosition($idPlanning, $user->getIdpersonnel());
        $result['canSetLastPosition'] = (bool)$result['canSetLastPosition'];
        return $this->json(['data' => $result]);
    }


    /**
     * @throws Exception
     */
    #[Route('/non-working-dates', name: 'api_non_working_dates', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Liste des jours non travaillé du planning')]
    public function getNonWorkingDates(Request $request): JsonResponse
    {
        $idPlanning = $request->headers->get('X-Planning-Id');

        if ($idPlanning !== null && !is_numeric($idPlanning) || $idPlanning < 0) {
            $this->logger->debug('Le paramètre idPlanning doit être un entier positif. Valeur reçue: {idPlanning}', [
                'idPlanning' => $idPlanning,
            ]);
            throw new BadRequestHttpException('Id du planning manquant');
        }

        $years = $request->query->get('years');

        $nonWorkingDates = $this->planningVueRepository->getNonWorkingDates($idPlanning, $years);

        $typesNonWorkingDates = $this->typesJourRepository->getAllTypesJour();


        return $this->json([
            'data' => [
                'nonWorkingDates' => $nonWorkingDates,
                'typesNonWorkingDates' => $typesNonWorkingDates
            ]
        ]);

    }

    #[Route('/non-working-dates/available-years', name: 'api_non_working_dates_available_years', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Liste des années disponibles pour les jours non travaillés du planning')]
    public function getAvailableYears(Request $request): JsonResponse
    {

        $anneesDisponibles = $this->planningVueRepository->getAvailableYears();


        return $this->json($anneesDisponibles);
    }


    /**
     * @throws Exception
     */
    #[Route('/mobile/display', name: 'api_mobile_get', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Affichage des évenement sur mobile')]
    public function getAffichageMobile(#[CurrentUser] Session $user) :JsonResponse{
        $idPersonnel = $user->getIdpersonnel();

        $result = $this->planningVueRepository->getAffichageMobile($idPersonnel, $this->logger);

        return $this->json(['data' => $result]);

    }


    /**
     * @throws Exception
     */
    #[Route('/mobile/settings', name: 'api_mobile_settings_get', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Affichage des évenement sur mobile')]
    public function getAffichageSettingsMobileSettings(#[CurrentUser] Session $user) :JsonResponse{

        $idPersonnel = $user->getIdpersonnel();

        $result = $this->planningVueRepository->getAffichageSettingsMobile($idPersonnel, $this->logger);

        return $this->json(['data' => $result]);
    }


    #[Route('/setLastPosition', name: 'api_setPosition', methods: ['POST'])]
    public function setPosition(#[CurrentUser] Session $user, Request $request): JsonResponse
    {
        $idPlanning = $request->headers->get('X-Planning-Id');

        if ($idPlanning !== null && !is_numeric($idPlanning) || $idPlanning < 0) {
            $this->logger->debug('Le paramètre idPlanning doit être un entier positif. Valeur reçue: {idPlanning}', [
                'idPlanning' => $idPlanning,
            ]);
            throw new BadRequestHttpException('Le paramètre idPlanning doit être un entier positif.');
        }


        $idPersonnel = $user->getIdpersonnel();
        $data = $request->toArray();
        $date = $data['date'] ?? null;

        if ($date === null) {
            $this->logger->debug('Le paramètre date est manquant dans le payload. Payload reçu ' .  json_encode($data));

            throw new BadRequestHttpException('Le paramètre date est manquant dans le payload.');
        }


        $this->logger->info('Requête API de sauvegarde de la position dernière reçue', [
            'idPersonnel' => $idPersonnel,
            'payload'     => $data
        ]);

        $result = $this->planningVueRepository->setPosition($idPlanning, $idPersonnel, $date, $this->logger);


        return $this->json(['data' => $result]);


    }


    /**
     * @throws Exception
     */
    #[Route('/mobile/save', name: 'api_mobile_settings_post', methods: ['POST'])]
    public function getAffichageSettingsMobileSettingsSave(#[CurrentUser] Session $user, Request $request): JsonResponse
    {
        $idPersonnel = $user->getIdpersonnel();
        $jsonPayload = $request->getContent();

        $this->logger->info('Requête API de sauvegarde de l\'affichage mobile reçue', [
            'idPersonnel' => $idPersonnel,
            'payload'     => $jsonPayload
        ]);

        $result = $this->planningVueRepository->getAffichageSettingsMobileSave($idPersonnel, $jsonPayload, $this->logger);

        return $this->json(['data' => $result]);


    }

    /**
     * @throws Exception
     */
    #[Route('/setLastVue', name: 'api_set_last_vue', methods: ['POST'])]
    #[OA\Response(response: 200, description: 'Met à jour la dernière vue d\'un utilisateur pour un planning donné')]
    #[OA\Response(response: 400, description: 'Paramètre idVue manquant ou invalide')]
    #[OA\Response(response: 500, description: 'Erreur serveur')]
    public function setLastVue(#[CurrentUser] Session $user, Request $request) :JsonResponse{

        $data = $request->toArray();

        if($data['idVue'] !== null && !is_numeric($data['idVue']) || $data['idVue'] < 0) {
            $this->logger->debug('Le paramètre idVue doit être un entier positif. Valeur reçue: {idVue}', [
                'idVue' => $data['idVue'],
            ]);
            throw new BadRequestHttpException('Id du planning manquant');
        }
            $this->planningVueRepository->setLastVue($user, $data['idVue']);
            return $this->json(['message' => 'La dernière vue a été mise à jour avec succès.']);
    }



    #[Route('/chantier/setLastVue', name: 'api_set_last_chantier_vue', methods: ['POST'])]
    #[OA\Response(response: 200, description: 'Met à jour la dernière vue chantier d\'un utilisateur ')]
    #[OA\Response(response: 400, description: 'Paramètre idVue manquant ou invalide')]
    #[OA\Response(response: 500, description: 'Erreur serveur')]
    public function setLastChantierVue(#[CurrentUser] Session $user, Request $request) :JsonResponse{

        $data = $request->toArray();

        if($data['idVue'] !== null && !is_numeric($data['idVue']) || $data['idVue'] < 0) {
            $this->logger->debug('Le paramètre idVue doit être un entier positif. Valeur reçue: {idVue}', [
                'idVue' => $data['idVue'],
            ]);
            throw new BadRequestHttpException('Id du planning manquant');
        }
        $this->planningVueRepository->setLastChantierVue($user, $data['idVue']);
        return $this->json(['message' => 'La dernière vue a été mise à jour avec succès.']);
    }


    /**
     * @throws Exception
     */
    #[Route('/non-working-dates', name: 'api_add_non-working-dates', methods: ['POST'])]
    #[IsGranted('VUE_CREATE_DATE', message: 'Vous n\'avez pas la permission de créer cette journée.')]
    public function addNonWorkingDates(Request $request, LoggerInterface $logger, #[CurrentUser] Session $user): JsonResponse
    {

        $idPlanning = $request->headers->get('X-Planning-Id');

        if (!$idPlanning) {
            throw new BadRequestHttpException('Id du planning manquant');
        }

        $data = $request->toArray();

        $result = $this->planningVueRepository->createNonWorkingDates($data, $idPlanning, $logger);

        $this->notifier->notifyPlanningChange(
            $idPlanning,
            'ADD_NON_WORKING_DAY',
            $user->getIdpersonnel(),
            ['date' => $data['nonWorkingDate'], 'id' => $result, 'typeJourCode' => $data['typeJourCode']]
        );

        return $this->json(['message' => 'Jours non travaillés ajoutés avec succès.', 'data' => $result]);
    }


    /**
     * @throws \Throwable
     */
    #[Route('/vue', name: 'api_vue_create', methods: ['POST'])]
    #[IsGranted('PLANNING_MANAGE', message: 'Vous n\'avez pas la permission de créer de vue.')]
    public function addVue(#[CurrentUser] Session $user, Request $request): JsonResponse
    {
        $idPlanning = $request->headers->get('X-Planning-Id');

        if (!$idPlanning) {
            throw new BadRequestHttpException('Id du planning manquant');
        }

        $data = $request->toArray();

        $planningVue = $data['planningVue'];

        if (!$planningVue){
            throw new BadRequestHttpException('Données de la vue manquantes');
        }

        $filtrePerso = $data['filtrePerso'];
        if (!$filtrePerso){
           $filtrePerso = [];
        }

        $utilisateursAutorises = $data['utilisateursAutorises'] ?? [];

        $result = $this->planningVueRepository->createVue($planningVue, $filtrePerso, $utilisateursAutorises, $idPlanning, $user->getIdpersonnel(),  $this->logger);

        $this->notifier->notifyPlanningChange(
            $idPlanning,
            'ADD_VUE',
            $user->getIdpersonnel(),
            ['vue' => $result]
        );

        return $this->json($result);

    }



    /**
     * @throws \Throwable
     */
    #[Route('/chantier/vue', name: 'api_chantier_vue_create', methods: ['POST'])]
    #[IsGranted('CHANTIER_MANAGE', message: 'Vous n\'avez pas la permission de créer de vue.')]
    public function addChantierVue(#[CurrentUser] Session $user, Request $request): JsonResponse
    {
        $idPlanning = $request->headers->get('X-Planning-Id');

        if (!$idPlanning) {
            throw new BadRequestHttpException('Id du planning manquant');
        }

        $data = $request->toArray();

        $planningVue = $data['planningVue'];

        if (!$planningVue){
            throw new BadRequestHttpException('Données de la vue manquantes');
        }


        $utilisateursAutorises = $data['utilisateursAutorises'] ?? [];

        $result = $this->planningVueRepository->createChantierVue($planningVue, $utilisateursAutorises, $idPlanning, $user->getIdpersonnel(),  $this->logger);

        $this->notifier->notifyPlanningChange(
            $idPlanning,
            'ADD_CHANTIER_VUE',
            $user->getIdpersonnel(),
            ['vue' => $result]
        );

        return $this->json($result);

    }

    /**
     * @throws Exception
     */
    #[Route('/{idDate}/non-working-dates', name: 'api_non-working-dates', methods: ['DELETE'])]
    #[IsGranted('VUE_EDIT_DATE', message: 'Vous n\'avez pas la permission de supprimer cette journée.')]
    public function deleteNonWorkingDates(Request $request, int $idDate, LoggerInterface $logger, #[CurrentUser] Session $user): JsonResponse
    {
        $idPlanning = $request->headers->get('X-Planning-Id');

        if (!$idPlanning) {
            throw new BadRequestHttpException('Id du planning manquant');
        }

        $result = $this->planningVueRepository->deleteNonWorkingDates($idDate);

        $this->notifier->notifyPlanningChange(
            $idPlanning,
            'DELETE_NON_WORKING_DAY',
            $user->getIdpersonnel(),
            ['date' => $result['date']]
        );

        return $this->json(['message' => 'Jours non travaillé supprimé avec succès.']);

    }

    #[Route('/vue/{id}/lock', methods: ['POST'])]
    #[IsGranted('PLANNING_MANAGE', message: 'Vous n\'avez pas la permission de lock cette vue.')]
    public function quickLock(int $id, #[CurrentUser] Session $user, LoggerInterface $logger, Request $request): JsonResponse
    {
        $idPlanning = $request->headers->get('X-Planning-Id');

        if (!$idPlanning) {
            throw new BadRequestHttpException('Id du planning manquant');
        }

        $cacheKey = 'edit_config_' . $idPlanning . '_' . $id;
        $currentUserId = $user->getIdPersonnel();

            $ownerId = $this->cache->get($cacheKey, function (ItemInterface $item) use ($currentUserId) {
                $item->expiresAfter(120);
                return $currentUserId;
            });


        if ($ownerId !== $currentUserId) {
            throw new ConflictHttpException("Cette configuration est actuellement en cours d'édition.");
        }

        $this->notifier->notifyPlanningChange(
            $idPlanning,
            'CONFIG_LOCKED',
            $currentUserId,
            ['IdPlanningVue' => $id]
        );

        return $this->json(['message' => 'La configuration a été verrouillée avec succès.']);
    }

    /**
     * @throws Exception
     * @throws \Throwable
     */
    #[Route('/vue/{id}', name: 'api_vue', methods: ['PUT'])]
    #[IsGranted('PLANNING_MANAGE', message: 'Vous n\'avez pas la permission de modifier cette vue.')]
    public function setvue(int $id, LoggerInterface $logger, Request $request): JsonResponse
    {
        $idPlanning = $request->headers->get('X-Planning-Id');
        if (!$idPlanning) {
            throw new BadRequestHttpException('Id du planning manquant');
        }

        $data = $request->toArray();

        $logger->debug('Données reçues pour la mise à jour de la vue {id}: {data}', [
            'id' => $id,
            'data' => $data,
        ]);

        $planningVue = $data['planningVue'];

        if (!$planningVue)
            throw new BadRequestHttpException('Données de la vue manquantes');{
        }

        $filtrePerso = $data['filtrePerso'];

        if (is_null($filtrePerso)){
            throw new BadRequestHttpException('Données du filtre perso manquantes');
        }

        $utilisateursAutorises = $data['utilisateursAutorises'] ?? [];


        $result = $this->planningVueRepository->setVue($id, $planningVue, $filtrePerso, $utilisateursAutorises, $logger);

        if (!$result) {
            return $this->json(['message' => 'La vue n\'a pas pu être mise à jour.'], 500);
        }

        return $this->json([ 'message' => 'Vue mise à jour avec succès.', 'data' => $result]);

    }



    #[Route('/non-working-dates/{id}', name: 'api_non_working_dates_put', methods: ['PUT'])]
    #[IsGranted('VUE_EDIT_DATE', message: 'Vous n\'avez pas la permission de modifier cette date.')]
    public function setDates(int $id, LoggerInterface $logger, Request $request): JsonResponse
    {
        $idPlanning = $request->headers->get('X-Planning-Id');
        if (!$idPlanning) {
            throw new BadRequestHttpException('Id du planning manquant');
        }

        $data = $request->toArray();

        $logger->debug('Données reçues pour la mise à jour de la date non travaillée {id}: {data}', [
            'id' => $id,
            'data' => $data,
        ]);

       $this->planningVueRepository->setDate($id, $data, $idPlanning, $logger);

        return $this->json([ 'message' => 'Vue mise à jour avec succès.'], 200);

    }


    /**
     * @throws \Throwable
     */
    #[Route('/chantier/vue/{id}', name: 'api_chantier_vue_set', methods: ['PUT'])]
    #[IsGranted('CHANTIER_MANAGE', message: 'Vous n\'avez pas la permission de créer de vue.')]
    public function setChantierVue(int $id, #[CurrentUser] Session $user, Request $request): JsonResponse
    {
        $idPlanning = $request->headers->get('X-Planning-Id');

        if (!$idPlanning) {
            throw new BadRequestHttpException('Id du planning manquant');
        }

        $data = $request->toArray();

        $planningVue = $data['planningVue'];

        if (!$planningVue){
            throw new BadRequestHttpException('Données de la vue manquantes');
        }

        $utilisateursAutorises = $data['utilisateursAutorises'] ?? [];

        $result = $this->planningVueRepository->setChantierVue(
            $id, $planningVue, $utilisateursAutorises, $idPlanning, $user->getIdpersonnel(),  $this->logger
        );

        $this->notifier->notifyPlanningChange(
            $idPlanning,
            'SET_CHANTIER_VUE',
            $user->getIdpersonnel(),
            ['vue' => $result]
        );

        return $this->json($result);

    }

    /**
     * @throws Exception
     */
    #[Route('/vue/{id}', name: 'api_vue_delete', methods: ['DELETE'])]
    #[IsGranted('PLANNING_DELETE', message: 'Vous n\'avez pas la permission de supprimer cette vue.')]
    public function deleteVue(int $id, LoggerInterface $logger, Request $request,  #[CurrentUser] Session $user): JsonResponse{
        $idPlanning = $request->headers->get('X-Planning-Id');
        if (!$idPlanning) {
            throw new BadRequestHttpException('Id du planning manquant');
        }


        $result = $this->planningVueRepository->deleteVue($id, $logger);

        if (!$result) {
            return $this->json(['message' => 'Aucune vue n\'a été supprimée (ID introuvable)'], 404);
        }


        $this->notifier->notifyPlanningChange(
            $idPlanning,
            'DELETE_VUE',
            $user->getIdpersonnel(),
            ['IdPlanningVue' => $id]
        );

        return $this->json($result);

    }



    #[Route('/chantier/vue/{id}', name: 'api_chantier_vue_delete', methods: ['DELETE'])]
    #[IsGranted('CHANTIER_DELETE', message: 'Vous n\'avez pas la permission de supprimer cette vue.')]
    public function deleteChantierVue(int $id, LoggerInterface $logger, Request $request,  #[CurrentUser] Session $user): JsonResponse{
        $idPlanning = $request->headers->get('X-Planning-Id');
        if (!$idPlanning) {
            throw new BadRequestHttpException('Id du planning manquant');
        }


        $result = $this->planningVueRepository->deleteChantierVue($id, $logger);

        if (!$result) {
            return $this->json(['message' => 'Aucune vue n\'a été supprimée (ID introuvable)'], 404);
        }


        $this->notifier->notifyPlanningChange(
            $idPlanning,
            'DELETE_CHANTIER_VUE',
            $user->getIdpersonnel(),
            ['IdPlanningVue' => $id]
        );

        return $this->json($result);

    }


}
