<?php

namespace App\Controller;

use App\Entity\Session;
use App\Repository\EmployeeRepository;
use App\Repository\SecurityRepository;
use App\Security\Voter\EmployeeVoter;
use App\Security\Voter\EquipeVoter;
use App\Service\EmployeeLockService;
use App\Service\MercureNotificationService;
use App\Service\PlanningPermissionHelper;
use Doctrine\DBAL\Exception;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use \Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Contracts\Cache\CacheInterface;

#[Route('/demo/employees')]
#[OA\Tag(name: 'Employés')]
class EmployeeController extends AbstractController
{
    public function __construct(
        private readonly EmployeeRepository         $employeeRepository,
        private readonly SecurityRepository         $securityRepository,
        private readonly LoggerInterface            $logger,
        private readonly PlanningPermissionHelper   $permissionHelper,
        private readonly EmployeeLockService        $lockService,
        private readonly MercureNotificationService $notifier,
    )
    {
    }


    /**
     * @throws InvalidArgumentException
     */
    #[Route('/lock/{id}', name: 'api_employees_check_lock', methods: ['GET'])]
    public function checkEmployeeLock(int $id, #[CurrentUser] Session $user): JsonResponse
    {
        $status = $this->lockService->getLockStatus($id, $user->getIdPersonnel());

        return $this->json($status);
    }

    /**
     * @throws Exception
     */
    #[Route('/planning', name: 'api_employees_list', methods: ['GET'])]
    #[IsGranted(EmployeeVoter::LIST, message: 'Vous n\'avez pas la permission de récupérer les employées.')]
    #[OA\Response(response: 200, description: 'Liste de tous les employés (Salariés et Intérimaires)')]
    public function list(Request $request, LoggerInterface $logger, #[CurrentUser] Session $user): JsonResponse
    {
        $droit = $this->permissionHelper->getPermissionDetails($user, 214, 22);

        if (!$droit['hasRight']) {
            return $this->json([
                'message' => 'Vous n\'avez pas la permission de récupérer les employées.'
            ], 403);
        }

        $idPlanningVue = $request->headers->get('X-PlanningVue-Id', null);

        $logger->debug("Récupération de TOUS les employés (sans filtres/pagination)");
        $employees = $this->employeeRepository->getEmployeelist($idPlanningVue);

        // Ajout de isLocked et lockedBy
        $employees = $this->lockService->attachLockStatus($employees, $user->getIdPersonnel());

        return $this->json(['data' => $employees]);
    }

    /**
     * @throws \Exception
     * @throws Exception
     * @throws InvalidArgumentException
     */
    #[Route('', name: 'api_employee', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Récupère les utilisateurs')]
    #[IsGranted(EmployeeVoter::LIST, message: 'Vous n\'avez pas la permission de récupérer les employées.')]
    public function getEmployees(#[CurrentUser] Session $user, LoggerInterface $logger): JsonResponse
    {
        $droit = $this->permissionHelper->getPermissionDetails($user, 214, 22);

        if (!$droit['hasRight']) {
            return $this->json([
                'message' => 'Vous n\'avez pas la permission de récupérer les employées.'
            ], 403);
        }

        $result = $this->employeeRepository->getUsers();
        $result = $this->lockService->attachLockStatus($result, $user->getIdPersonnel());
        return $this->json(['data' => $result]);

    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    #[Route('/search', name: 'api_employees_list_search', methods: ['GET'])]
    #[OA\Parameter(name: 'limit', in: 'query', description: 'Limite de résultats', schema: new OA\Schema(type: 'integer', default: 20))]
    #[OA\Parameter(name: 'pageNum', in: 'query', description: 'Numéro de page pour la pagination', schema: new OA\Schema(type: 'integer', default: 1))]
    #[OA\Parameter(name: 'q', in: 'query', description: '', schema: new OA\Schema(type: 'string', default: ''))]
    #[OA\Response(response: 200, description: 'Liste de tous les employés (Salariés et Intérimaires)')]
    #[IsGranted(EmployeeVoter::LIST, message: 'Vous n\'avez pas la permission de récupérer les employées.')]
    public function search(Request $request, LoggerInterface $logger, #[CurrentUser] Session $user): JsonResponse
    {
        $hasSearchParameters =
            $request->query->has('limit')
            || $request->query->has('pageNum')
            || $request->query->has('q')
            || $request->query->has('code');

        if ($hasSearchParameters) {

            $limit = max(1, min(
                $request->query->getInt('limit', 20),
                100
            ));

            $pageNumber = max(
                1,
                $request->query->getInt('pageNum', 1)
            );

            $q = $request->query->get('q', '');
            $codes = $request->query->get('code', '');

            $logger->debug("Récupération de la liste des employés", ['limit' => $limit, 'pageNum' => $pageNumber, 'q' => $q, 'codes' => $codes]);

            $result = $this->employeeRepository->getEmployeePagination($limit, $pageNumber, $q, $codes, $logger);

            $employees = $this->lockService->attachLockStatus($result, $user->getIdPersonnel());

            return $this->json([
                'data' => $employees,
                'TotalLignes' => $result['TotalLignes']
            ]);
        } else {
            throw new BadRequestHttpException('Vous devez fournir au moins un paramètre de recherche (limit, pageNum, q ou code).');
        }

    }

    /**
     * @throws Exception
     */
    #[Route('/{id}', name: 'api_employees_show', methods: ['GET'])]
    #[OA\Parameter(name: 'id', in: 'path', description: 'ID de l\'employé', schema: new OA\Schema(type: 'integer'))]
    # #[OA\Parameter(name: 'type', in: 'query', description: 'Salarie ou Interim', schema: new OA\Schema(type: 'string', enum: ['Salarie', 'Interim']))]
    #[OA\Response(response: 200, description: 'Détails d\'un employé spécifique')]
    #[OA\Response(response: 404, description: 'Employé introuvable')]
    public function getEmployee(int $id, Request $request, #[CurrentUser] Session $user): JsonResponse
    {
        $droit = $this->permissionHelper->getPermissionDetails($user, 214, 22);

        if (!$droit['hasRight'] || $user->getIdpersonnel() !== $id) {
            throw new AccessDeniedHttpException('Vous n\'avez pas la permission.');
        }

        // Appel avec paramètres => La PS renvoie une seule ligne (ou vide)
        $result = $this->employeeRepository->getEmployeelist(null, $id);

        if (empty($result)) {
            return $this->json(['message' => 'Employé non trouvé'], 404);
        }

        return $this->json(['data' => $result]);
    }


    /**
     * @throws InvalidArgumentException
     */
    #[Route('/{id}/lock', name: 'api_employees_lock', methods: ['POST'])]
    #[IsGranted(EquipeVoter::UPDATE, message: 'Vous n\'avez pas la permission de verrouiller cet employé.')]
    public function lockEmployee(int $id, #[CurrentUser] Session $user, Request $request): JsonResponse
    {
        $nomPersonnel = $request->toArray()['nomPersonnel'] ?? null;
        if (!$nomPersonnel) {
            return $this->json([
                'message' => 'Le champ "nomPersonnel" est obligatoire pour verrouiller un employé.'
            ], 400);
        }

        $lockData = $this->lockService->acquireLock($id, $user->getIdPersonnel(), $nomPersonnel);


        if ($lockData['status'] && $lockData['updatedById'] !== $user->getIdPersonnel()) {
            return $this->json([
                'isLocked'   => $lockData,
                'idLockedBy' => $lockData['updatedById'],
                'message'    => 'Cet employé est actuellement verrouillé par un autre utilisateur.',
            ], 409);
        }

        $this->notifier->notifyEmployeeLockStatus(
            $id,
            true,
            $user->getIdPersonnel(),
            $lockData['updatedByNom'],
            $lockData['expiresAt']
        );

        return $this->json([
            'isLocked'   => $lockData,
            'idLockedBy' => $user->getIdPersonnel(),
        ]);
    }

    #[Route('/{id}/unlock', name: 'api_employees_unlock', methods: ['DELETE'])]
    #[IsGranted(EquipeVoter::UPDATE, message: 'Vous n\'avez pas la permission de déverrouiller cet employé.')]
    public function unlockEmployee(int $id, #[CurrentUser] Session $user): JsonResponse
    {
        if (!$this->lockService->releaseLock($id, $user->getIdPersonnel())) {
            return $this->json(['message' => 'Vous ne pouvez pas lever le verrou d\'un autre utilisateur.'], 403);
        }
        $this->notifier->notifyEmployeeLockStatus($id, false, $user->getIdPersonnel());
        return $this->json(['isLocked' => false]);
    }


    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    #[Route('/équipe/{id}', name: 'api_employees_update', methods: ['PUT'])]
    #[OA\Parameter(name: 'id', in: 'path', description: 'ID de l\'employé', schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(description: 'Données pour mettre à jour l\'équipe de l\'employé', required: true, content: new OA\JsonContent(
        type: 'object',
        properties: [
            new OA\Property(property: 'Type', type: 'string', description: 'Type de l\'employé (SALARIE ou INTERIM)', enum: ['SALARIE', 'INTERIM']),
            new OA\Property(property: 'IdEquipe', type: 'integer', description: 'ID de l\'équipe à laquelle l\'employé appartient')
        ],
        required: ['Type', 'IdEquipe']
    ))]
    #[OA\Response(response: 200, description: 'Employé mis à jour avec succès')]
    #[OA\Response(response: 400, description: 'Requête invalide')]
    #[OA\Response(response: 404, description: 'Employé introuvable')]
    #[OA\Response(response: 409, description: 'Employé verrouillé par un autre utilisateur')]
    #[IsGranted(EquipeVoter::UPDATE, message: 'Vous n\'avez pas la permission de modifier les employées.')]
    public function update(int $id, Request $request, LoggerInterface $logger, #[CurrentUser] Session $user): JsonResponse
    {
        $currentUserId = $user->getIdPersonnel();

        // 1. Vérifier que l'employé n'est pas verrouillé par un autre utilisateur
        $lockStatus = $this->lockService->getLockStatus($id, $currentUserId);
        if ($lockStatus['isLocked']['status']) {
            return $this->json([
                'isLocked'   => true,
                'idLockedBy' => $lockStatus['lockedBy'],
                'message'    => 'Cet employé est actuellement verrouillé par un autre utilisateur.',
            ], 409);
        }

        $data = $request->toArray();

        if (!array_key_exists('IdEquipe', $data)) {
            return $this->json([
                'message' => 'Le champ "IdEquipe" est obligatoire.'
            ], 400);
        }

        $type = $data['Type'] ?? null;
        if (!$type || !in_array($type, ['SALARIE', 'INTERIM'])) {
            return $this->json(['message' => 'Le champ "type" (SALARIE ou INTERIM) est obligatoire dans le body.'], 400);
        }

        $lignesModifiees = $this->employeeRepository->setEquipeEmployee($id, $data, $logger);

        if ($lignesModifiees === 0) {
            return $this->json(['message' => 'Employé introuvable.'], 404);
        }

        $newTeamId = $data['IdEquipe'] !== null ? (int) $data['IdEquipe'] : null;

        $this->notifier->notifyEmployeeTeamUpdated($id, $newTeamId, $currentUserId);

        if ($this->lockService->releaseLock($id, $currentUserId)) {
            $this->notifier->notifyEmployeeLockStatus($id, false, $currentUserId);
        }

        return $this->json(['message' => 'Employé mis à jour avec succès']);
    }


}
