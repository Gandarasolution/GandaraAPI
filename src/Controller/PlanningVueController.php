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
use Symfony\Component\Routing\Attribute\Route;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[Route('/api/planning')]
#[OA\Tag(name: 'Configurations/Vues')]
class PlanningVueController extends AbstractController
{

    public function __construct(
        private readonly LoggerInterface $logger,
        private MercureNotificationService $notifier,
        private EntityManagerInterface $entityManager,
        private PlanningVueRepository $planningVueRepository,
        private CacheInterface $cache
    )
    {}

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
            return $this->json(['error' => 1, 'message' => 'Le paramètre idPlanning doit être un entier positif.'], 400);
        }

        try {
            $result = $this->planningVueRepository->getLastVue($user, $idPlanning);
            return $this->json(['error' => 0, 'data' => $result]);
        }catch (Exception $e) {
            $this->logger->error('Erreur lors de la récupération de la dernière vue pour l\'utilisateur {userId}: {message}', [
                'userId' => $user->getIdpersonnel(),
                'message' => $e->getMessage(),
            ]);
            return $this->json(['error' => 1, 'message' => 'Une erreur est survenue lors de la récupération de la dernière vue: ' . $e->getMessage()], 500);
        }
    }



    #[Route('/vue/{id}', name: 'api_config', methods: ['GET'])]
    #[OA\Parameter(name: 'id', in: 'path', description: 'ID de la vue', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Récupère les détails d\'une vue spécifique')]
    public function getVue(int $id) :JsonResponse {
        try {
            $result = $this->planningVueRepository->getVue($id, $this->logger);

            return $this->json(['error' => 0, 'data' => $result]);
        }catch(\Exception $e){
            $this->logger->debug('Erreur lors de la récupération de la vue {id}: {message}', [
                'id' => $id,
                'message' => $e->getMessage(),
            ]);
            return $this->json(['error' => 1, 'message' => $e->getMessage()], 500);
        }
    }


    //GET /api/planning/vue/user/:userId?idPlanning=:id- Configs d'un utilisateur
    #[Route('/vue/user/{userId}', name: 'api_configs_user', methods: ['GET'])]
    #[OA\Parameter(name: 'userId', in: 'path', description: 'ID de l\'utilisateur', schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'idPlanning', in: 'query', description: 'ID de planning (optionnel)', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Liste des configurations de l\'utilisateur et des jours non travaillé du planning')]
    public function getUserConfigs(int $userId, Request $request): JsonResponse
    {
        try {
            $IdPlanning = $request->query->get('idPlanning');

            if ($IdPlanning !== null && !is_numeric($IdPlanning) || $IdPlanning < 0) {
                return $this->json(['error' => 1, 'message' => 'Le paramètre idPlanning doit être un entier positif.'], 400);
            }

            $configs = $this->planningVueRepository->getConfigUser($userId, $IdPlanning);
            return $this->json(['error' => 0, 'data' => $configs]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération des configs pour l\'utilisateur {userId}: {message}', [
                'userId' => $userId,
                'message' => $e->getMessage(),
            ]);
            return $this->json(['error' => 1, 'message' => 'Une erreur est survenue lors de la récupération des configurations:' . $e->getMessage()], 500);
        }
    }


    #[Route('/non-working-dates', name: 'api_non_working_dates', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Liste des jours non travaillé du planning')]
    public function getNonWorkingDates(Request $request): JsonResponse
    {
        try {
            $idPlanning = $request->headers->get('X-Planning-Id');

            if ($idPlanning !== null && !is_numeric($idPlanning) || $idPlanning < 0) {
                $this->logger->debug('Le paramètre idPlanning doit être un entier positif. Valeur reçue: {idPlanning}', [
                    'idPlanning' => $idPlanning,
                ]);
                return $this->json(['error' => 1, 'message' => 'Le paramètre idPlanning doit être un entier positif.'], 400);
            }

            $result = $this->planningVueRepository->getNonWorkingDates($idPlanning);

            return $this->json(['error' => 0, 'data' => $result]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération des jours non travaillés pour le planning {idPlanning}: {message}', [
                'idPlanning' => $idPlanning,
                'message' => $e->getMessage(),
            ]);
            return $this->json(['error' => 1, 'message' => 'Une erreur est survenue lors de la récupération des jours non travaillés: ' . $e->getMessage()], 500);
        }
    }

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
            return $this->json(['error' => 1, 'message' => 'Le paramètre idVue doit être un entier positif.'], 400);
        }
        try {
            $this->planningVueRepository->setLastVue($user, $data['idVue']);
            return $this->json(['error' => 0]);
        }catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération de la dernière vue pour l\'utilisateur {userId}: {message}', [
                'userId' => $user->getIdpersonnel(),
                'message' => $e->getMessage(),
            ]);
            return $this->json(['error' => 1, 'message' => 'Une erreur est survenue lors de la récupération de la dernière vue: ' . $e->getMessage()], 500);
        }
    }


    // POST /api/planning/{idPlanning}/non-working-dates Ajout d'un jour non travaillé
    #[Route('/non-working-dates', name: 'api_add_non-working-dates', methods: ['POST'])]
    public function addNonWorkingDates(Request $request, LoggerInterface $logger, #[CurrentUser] Session $user): JsonResponse
    {
        try {
            $idPlanning = $request->headers->get('X-Planning-Id');

            if (!$idPlanning) {
                return $this->json(['error' => 1, 'message' => 'Id du planning manquant'], 400);
            }

            $data = $request->toArray();

            $result = $this->planningVueRepository->createNonWorkingDates($data, $idPlanning, $logger);

            $this->notifier->notifyPlanningChange(
                $idPlanning,
                'ADD_NON_WORKING_DAY',
                $user->getIdpersonnel(),
                ['date' => $data['nonWorkingDate'], 'id' => $result]
            );

            return $this->json(['error' => 0, 'message' => 'Jours non travaillés ajoutés avec succès.', 'data' => $result]);
        } catch (\Exception $e) {
            return $this->json(['error' => 1, 'message' =>  $e->getMessage()], 500);
        }
    }


    #[Route('/vue', name: 'api_vue_create', methods: ['POST'])]
    public function addVue(#[CurrentUser] Session $user, Request $request): JsonResponse
    {
        try {
            $idPlanning = $request->headers->get('X-Planning-Id');

            if (!$idPlanning) {
                return $this->json(['error' => 1, 'message' => 'Id du planning manquant'], 400);
            }

            $data = $request->toArray();

            $planningVue = $data['planningVue'];

            if (!$planningVue){
                return $this->json(['error' => 1, 'message' => 'Données de la vue manquantes'], 400);
            }

            $filtrePerso = $data['filtrePerso'];
            if (!$filtrePerso){
               $filtrePerso = [];
            }

            $result = $this->planningVueRepository->createVue($planningVue, $filtrePerso, $idPlanning, $user->getIdpersonnel(),  $this->logger);

            if ($result['error'] === 1){
                return $this->json(['error' => 1, 'message' => $result['message']], 500);
            }
            $this->notifier->notifyPlanningChange(
                $idPlanning,
                'ADD_VUE',
                $user->getIdpersonnel(),
                ['vue' => $result['data']]
            );

            return $this->json($result);
        } catch (\Exception $e) {
            return $this->json(['error' => 1, 'message' =>  $e->getMessage()], 500);
        }
    }

    #[Route('/{idDate}/non-working-dates', name: 'api_non-working-dates', methods: ['DELETE'])]
    public function deleteNonWorkingDates(Request $request, int $idDate, LoggerInterface $logger, #[CurrentUser] Session $user): JsonResponse
    {
        try {
            $idPlanning = $request->headers->get('X-Planning-Id');

            if (!$idPlanning) {
                return $this->json(['error' => 1, 'message' => 'Id du planning manquant'], 400);
            }

            $result = $this->planningVueRepository->deleteNonWorkingDates($idDate);

            $this->notifier->notifyPlanningChange(
                $idPlanning,
                'DELETE_NON_WORKING_DAY',
                $user->getIdpersonnel(),
                ['date' => $result['date']]
            );

            return $this->json(['error' => 0, 'message' => 'Jours non travaillé supprimé avec succès.']);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la suppression d\'un jour non travaillé: {message}', [
                'message' => $e->getMessage(),
            ]);
            return $this->json(['error' => 1, 'message' =>  $e->getMessage()], 500);
        }
    }

    #[Route('/vue/{id}/lock', methods: ['POST'])]
    public function quickLock(int $id, #[CurrentUser] Session $user, LoggerInterface $logger, Request $request): JsonResponse
    {
        $idPlanning = $request->headers->get('X-Planning-Id');

        if (!$idPlanning) {
            return $this->json(['error' => 1, 'message' => 'Id du planning manquant'], 400);
        }

        $cacheKey = 'edit_config_' . $idPlanning . '_' . $id;
        $currentUserId = $user->getIdPersonnel();

        try {
            $ownerId = $this->cache->get($cacheKey, function (ItemInterface $item) use ($currentUserId) {
                $item->expiresAfter(120);
                return $currentUserId;
            });
        } catch (InvalidArgumentException $e) {
            $logger->debug('Erreur lors de la récupération du verrou pour la configuration ' . $id, ['exception' => $e]);
            return $this->json(['error' => 1, 'message' => 'Erreur'], 500);
        }

        if ($ownerId !== $currentUserId) {
            return $this->json(['error' => 409, 'message' => 'Cette configuration est actuellement en cours d\'édition.'], 409);
        }

        $this->notifier->notifyPlanningChange(
            $idPlanning,
            'CONFIG_LOCKED',
            $currentUserId,
            ['IdPlanningVue' => $id]
        );

        return $this->json(['error' => 0]);
    }

    #[Route('/vue/{id}', name: 'api_vue', methods: ['PUT'])]
    public function setvue(int $id, LoggerInterface $logger, Request $request): JsonResponse{
        $idPlanning = $request->headers->get('X-Planning-Id');
        if (!$idPlanning) {
            return $this->json(['error' => 1, 'message' => 'Id du planning manquant'], 400);
        }

        $data = $request->toArray();

        $logger->debug('Données reçues pour la mise à jour de la vue {id}: {data}', [
            'id' => $id,
            'data' => $data,
        ]);

        $planningVue = $data['planningVue'];

        if (!$planningVue)
            return $this->json(['error' => 1, 'message' => 'Données de la vue manquantes'], 400);{
        }

        $filtrePerso = $data['filtrePerso'];

        if (is_null($filtrePerso)){
            return $this->json(['error' => 1, 'message' => 'Données du filtre perso manquantes'], 400);

        }

        try {
            $result = $this->planningVueRepository->setVue($id, $planningVue, $filtrePerso, $logger);

            if (!$result) {
                return $this->json(['error' => 1, 'message' => 'La vue n\'a pas pu être mise à jour.'], 500);
            }

            return $this->json(['error' => 0, 'message' => 'Vue mise à jour avec succès.', 'data' => $result]);
        }
        catch (\Exception $e) {
            $logger->error('Erreur lors de la mise à jour de la vue {id}: {message}', [
                'id' => $id,
                'message' => $e->getMessage(),
            ]);
            return $this->json(['error' => 1, 'message' => 'Une erreur est survenue lors de la mise à jour de la vue: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/vue/{id}', name: 'api_vue_delete', methods: ['DELETE'])]
    public function deleteVue(int $id, LoggerInterface $logger, Request $request,  #[CurrentUser] Session $user): JsonResponse{
        $idPlanning = $request->headers->get('X-Planning-Id');
        if (!$idPlanning) {
            return $this->json(['error' => 1, 'message' => 'Id du planning manquant'], 400);
        }


        try {
            $result = $this->planningVueRepository->deleteVue($id, $logger);

            if ($result['error'] === 1 ) {
                return $this->json(['error' => 1, 'message' => "La vue n'a pas pus être supprimé"], 500);
            }

            $this->notifier->notifyPlanningChange(
                $idPlanning,
                'DELETE_VUE',
                $user->getIdpersonnel(),
                ['IdPlanningVue' => $id]
            );

            return $this->json($result);
        }
        catch (\Exception $e) {
                $logger->error('Erreur lors de la mise à jour de la vue {id}: {message}', [
                    'id' => $id,
                    'message' => $e->getMessage(),
                ]);
                return $this->json(['error' => 1, 'message' => 'Une erreur est survenue lors de la suppression de la vue: ' . $e->getMessage()], 500);
        }
    }
}
