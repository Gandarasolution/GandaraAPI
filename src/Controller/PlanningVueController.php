<?php

namespace App\Controller;

use App\Entity\Session;
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
        private MercureNotificationService $notifier,
        private PlanningVueRepository $planningVueRepository,
        private CacheInterface $cache
    )
    {}

    /**
     * @throws \Exception
     */
    #[Route('/vue/users', name: 'vueUsers', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Récupère les utilisateurs')]
    public function getVueUsers(): JsonResponse
    {
        $result = $this->planningVueRepository->getUsers();
        return $this->json(['data' => $result]);

    }


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
    #[Route('/vue/user/{userId}', name: 'api_configs_user', methods: ['GET'])]
    #[OA\Parameter(name: 'userId', in: 'path', description: 'ID de l\'utilisateur', schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'idPlanning', in: 'query', description: 'ID de planning (optionnel)', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Liste des configurations de l\'utilisateur et des jours non travaillé du planning')]
    public function getUserConfigs(int $userId, Request $request): JsonResponse
    {
        $IdPlanning = $request->query->get('idPlanning');

        if ($IdPlanning !== null && !is_numeric($IdPlanning) || $IdPlanning < 0) {
            throw new BadRequestHttpException('Id du planning manquant');
        }

        $configs = $this->planningVueRepository->getConfigUser($userId, $IdPlanning);
        return $this->json(['data' => $configs]);

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

        $result = $this->planningVueRepository->getNonWorkingDates($idPlanning);

        return $this->json(['data' => $result]);

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
            ['date' => $data['nonWorkingDate'], 'id' => $result]
        );

        return $this->json(['message' => 'Jours non travaillés ajoutés avec succès.', 'data' => $result]);
    }


    /**
     * @throws \Throwable
     */
    #[Route('/vue', name: 'api_vue_create', methods: ['POST'])]
    #[IsGranted('VUE_CREATE', message: 'Vous n\'avez pas la permission de créer de vue.')]
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
            ['vue' => $result['data']]
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
    #[IsGranted('VUE_LOCK', message: 'Vous n\'avez pas la permission de lock cette vue.')]
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
     */
    #[Route('/vue/{id}', name: 'api_vue', methods: ['PUT'])]
    #[IsGranted('VUE_EDIT', message: 'Vous n\'avez pas la permission de modifier cette vue.')]
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

    /**
     * @throws Exception
     */
    #[Route('/vue/{id}', name: 'api_vue_delete', methods: ['DELETE'])]
    #[IsGranted('VUE_EDIT', message: 'Vous n\'avez pas la permission de supprimer cette vue.')]
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




}
