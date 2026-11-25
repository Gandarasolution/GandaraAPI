<?php

namespace App\Controller;

use App\Entity\Session;
use App\Repository\EquipeRepository;
use App\Security\Voter\EquipeVoter;
use App\Service\MercureNotificationService;
use App\Service\TeamLockService;
use Doctrine\DBAL\Exception;
use OpenApi\Attributes as OA;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\EmployeeLockService;

#[Route('/demo/equipes')]
#[OA\Tag(name: 'Équipes')]
class PlanningEquipeController extends AbstractController
{

    /**
     * @param EquipeRepository $repository
     */
    public function __construct(
        private readonly EquipeRepository $repository,
        private readonly LoggerInterface $logger,
        private readonly TeamLockService $teamLockService,
        private readonly MercureNotificationService $notifier
    ){}


    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    #[Route('', name: 'equipe_planning_list', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Liste de toutes les équipes')]
    public function list(Request $request, #[CurrentUser] Session $user): JsonResponse
    {
        $idPlanningVue = $request->headers->get('X-PlanningVue-Id');

        $equipes = $this->repository->getAllEquipes((int) $idPlanningVue);
        $equipes = $this->teamLockService->attachLockStatus($equipes, $user->getIdpersonnel());

        return $this->json(['data' => $equipes]);
    }


    /**
     * @throws InvalidArgumentException
     */
    #[Route('/{id}/personnel', name: 'equipe_planning_list_personnel', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted(EquipeVoter::VIEW, message: 'Vous n\'avez pas la permission de récupérer le personnel de cette équipe.')]
    #[OA\Response(response: 200, description: 'Liste de tout le personnel d\'une équipe')]
    #[OA\Parameter(name: 'id', in: 'path', description: 'ID de l\'équipe', schema: new OA\Schema(type: 'integer'))]
    public function listPersonnel(
        int $id,
        #[CurrentUser] Session $user,
        EmployeeLockService $lockService
    ): JsonResponse {
        $equipes = $this->repository->getPersonnelEquipe($id, $user->getIdpersonnel(), $this->logger);

        $equipes = $lockService->attachLockStatus($equipes, $user->getIdpersonnel());

        return $this->json(['data' => $equipes]);
    }

    #[Route('/{id}/check-delete', name: 'equipe_planning_check_delete', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted(EquipeVoter::DELETE)]
    #[OA\Response(response: 200, description: 'Vérifie si une équipe peut être supprimée')]
    #[OA\Parameter(name: 'id', in: 'path', description: 'ID de l\'équipe', schema: new OA\Schema(type: 'integer'))]
    public function checkDelete(int $id, Request $request, #[CurrentUser] Session $user): JsonResponse
    {
        $result = $this->repository->getDeleteImpact($id);

        if ($result === null) {
            return $this->json([
                'checkRequired' => false,
            ]);
        }

        return $this->json(['checkRequired' => true, 'message' => $result]);
    }

    #[Route('', name: 'equipe_planning_create', methods: ['POST'])]
    #[IsGranted(EquipeVoter::CREATE)]
    #[OA\RequestBody(
        description: 'Les informations pour créer une équipe',
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'Nom', type: 'string', description: 'Le nom de l\'équipe'),
            ],
            type: 'object'
        )
    )]
    #[OA\Response(response: 200, description: 'Équipe créée avec succès')]
    public function create(Request $request, #[CurrentUser] Session $user): JsonResponse
    {
        $data = $request->toArray();
        $designationEquipe = $data['Nom'] ?? null;

        if (!$designationEquipe) {
            return $this->json(['error' => 'Le nom de l\'équipe est requis'], 400);
        }

        $currentUserId = $user->getIdpersonnel();
        $createdTeam = $this->repository->createEquipe($designationEquipe, $currentUserId, $this->logger);

        // Notification Mercure
        $this->notifier->notifyTeamChange('TEAM_CREATED', $currentUserId, [
            'equipe' => $createdTeam ?? ['nom' => $designationEquipe],
        ]);

        return $this->json([
            'message' => 'Équipe créée avec succès',
            'data'    => $createdTeam,
        ]);
    }


    /**
     * @throws InvalidArgumentException
     */
    #[Route('/{id}/lock', name: 'api_teams_lock', methods: ['POST'])]
    #[IsGranted(EquipeVoter::UPDATE, message: 'Vous n\'avez pas la permission de verrouiller cette équipe.')]
    public function lockTeam(
        int $id,
        #[CurrentUser] Session $user,
        Request $request
    ): JsonResponse {

        $nomPersonnel = $request->toArray()['nomPersonnel'] ?? null;
        if (!$nomPersonnel) {
            return $this->json([
                'message' => 'Le champ "nomPersonnel" est obligatoire pour verrouiller un employé.'
            ], 400);
        }

        $currentUserId = $user->getIdPersonnel();
        $lockData = $this->teamLockService->acquireLock($id, $currentUserId, $nomPersonnel);

        if ($lockData['status'] && $lockData['updatedById'] !== $currentUserId) {
            return $this->json([
                'isLocked'   => $lockData,
                'idLockedBy' => $lockData['updatedById'],
                'message'    => 'Cet equipe est actuellement verrouillé par un autre utilisateur.',
            ], 409);
        }


        $this->notifier->notifyTeamLockStatus(
            $id,
            true,
            $currentUserId,
            $lockData['updatedByNom'],
            $lockData['expiresAt']
        );

        return $this->json([
            'isLocked'   => true,
            'idLockedBy' => $currentUserId,
            'expiresIn'  => $lockData['expiresAt'],
        ]);
    }



    #[Route('/{id}/unlock', name: 'api_teams_unlock', methods: ['DELETE'])]
    #[IsGranted(EquipeVoter::UPDATE, message: 'Vous n\'avez pas la permission de déverrouiller cette équipe.')]
    public function unlockTeam(int $id, #[CurrentUser] Session $user): JsonResponse
    {
        $currentUserId = $user->getIdPersonnel();
        $released = $this->teamLockService->releaseLock($id, $currentUserId);

        if (!$released) {
            return $this->json([
                'isLocked' => true,
                'message'  => 'Vous ne pouvez pas lever le verrou d\'un autre utilisateur.',
            ], 403);
        }

        $this->notifier->notifyTeamLockStatus($id, false, $currentUserId);

        return $this->json([
            'isLocked' => false,
        ]);
    }


    /**
     * @throws InvalidArgumentException
     */
    #[Route('/{id}', name: 'equipe_planning_update', requirements: ['id' => '\d+'], methods: ['PUT'])]
    #[IsGranted(EquipeVoter::UPDATE)]
    #[OA\Parameter(name: 'id', in: 'path', description: 'ID de l\'équipe', schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(
        description: 'Les nouvelles informations de l\'équipe',
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'Nom', type: 'string', description: 'Le nouveau nom de l\'équipe'),
            ],
            type: 'object'
        )
    )]
    #[OA\Response(response: 200, description: 'Équipe modifiée avec succès')]
    #[OA\Response(response: 404, description: 'Équipe non trouvée')]
    #[OA\Response(response: 409, description: 'Équipe verrouillée par un autre utilisateur')]
    public function update(Request $request, int $id, #[CurrentUser] Session $user): JsonResponse
    {
        $currentUserId = $user->getIdpersonnel();

        // 1. Vérification Redis : bloquée si verrouillée par un autre utilisateur
        $lockStatus = $this->teamLockService->getLockStatus($id, $currentUserId);
        if ($lockStatus['status']) {
            return $this->json([
                'isLocked'   => true,
                'idLockedBy' => $lockStatus['lockedBy'],
                'message'    => 'Cette équipe est actuellement verrouillée par un autre utilisateur.',
            ], 409);
        }

        $data = $request->toArray();
        $newName = $data['Nom'] ?? null;

        if (!$newName) {
            return $this->json(['error' => 'Le nouveau nom de l\'équipe est requis'], 400);
        }

        $this->repository->updateEquipe($id, $newName, $currentUserId, $this->logger);

        // 2. Notification Mercure
        $this->notifier->notifyTeamChange('TEAM_UPDATED', $currentUserId, [
            'idEquipe' => $id,
            'nom'      => $newName,
        ]);

        return $this->json(['message' => 'Équipe mise à jour avec succès']);
    }

    /**
     * @throws InvalidArgumentException
     */
    #[Route('/{id}', name: 'equipe_planning_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    #[IsGranted(EquipeVoter::DELETE)]
    #[OA\Parameter(name: 'id', in: 'path', description: 'ID de l\'équipe', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Équipe supprimée avec succès')]
    #[OA\Response(response: 409, description: 'Équipe verrouillée par un autre utilisateur')]
    public function delete(int $id, #[CurrentUser] Session $user, Request $request): JsonResponse
    {
        $currentUserId = $user->getIdpersonnel();

        // 1. Vérification Redis : bloquée si verrouillée par un autre utilisateur
        $lockStatus = $this->teamLockService->getLockStatus($id, $currentUserId);
        if ($lockStatus['status']) {
            return $this->json([
                'isLocked'   => true,
                'idLockedBy' => $lockStatus['lockedBy'],
                'message'    => 'Cette équipe est actuellement verrouillée par un autre utilisateur.',
            ], 409);
        }

        $data = $request->toArray();

        $this->repository->deleteEquipe($id, $data['numberEmployeeCheck'] ?? 0, $currentUserId, $this->logger);

        // 2. Libération forcée du verrou Redis s'il existait
        $this->teamLockService->releaseLock($id, $currentUserId, true);

        // 3. Notification Mercure
        $this->notifier->notifyTeamChange('TEAM_DELETED', $currentUserId, [
            'idEquipe' => $id,
        ]);

        return $this->json(['message' => 'Équipe supprimée avec succès']);
    }




}
