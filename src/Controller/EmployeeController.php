<?php

namespace App\Controller;

use App\Entity\Session;
use App\Repository\EmployeeRepository;
use App\Repository\SecurityRepository;
use Doctrine\DBAL\Exception;
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

#[Route('/api/employees')]
#[OA\Tag(name: 'Employés')]
class EmployeeController extends AbstractController
{
    public function __construct(
        private readonly EmployeeRepository $employeeRepository,
        private readonly SecurityRepository $securityRepository,
        private readonly LoggerInterface $logger
    ){}



    /**
     * @throws Exception
     */
    #[Route('', name: 'api_employees_list', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Liste de tous les employés (Salariés et Intérimaires)')]
    public function list(Request $request, LoggerInterface $logger, #[CurrentUser] Session $user): JsonResponse
    {
        $droit = $this->securityRepository->getPermission($user, $logger);

        if (!in_array($droit, [23, 22])) {
            return $this->json([
                'message' => 'Vous n\'avez pas la permission de récupérer les employées.'
            ], 403);
        }


        $idPlanningVue = $request->headers->get('X-PlanningVue-Id', null);

        $logger->debug("Récupération de TOUS les employés (sans filtres/pagination)");
        $employees = $this->employeeRepository->getEmployeelist($idPlanningVue);
        return $this->json(['data' => $employees]);


    }

    /**
     * @throws Exception
     */
    #[Route('/search', name: 'api_employees_list_search', methods: ['GET'])]
    #[OA\Parameter(name: 'limit', in: 'query', description: 'Limite de résultats', schema: new OA\Schema(type: 'integer', default: 20))]
    #[OA\Parameter(name: 'pageNum', in: 'query', description: 'Numéro de page pour la pagination', schema: new OA\Schema(type: 'integer', default: 1))]
    #[OA\Parameter(name: 'q', in: 'query', description: '', schema: new OA\Schema(type: 'string', default: ''))]
    #[OA\Response(response: 200, description: 'Liste de tous les employés (Salariés et Intérimaires)')]
    public function search(Request $request, LoggerInterface $logger, #[CurrentUser] Session $user): JsonResponse
    {
        $droit = $this->securityRepository->getPermission($user, $logger);

        if (!in_array($droit, [23, 22])) {
            return $this->json([
                'message' => 'Vous n\'avez pas la permission de récupérer les employées.'
            ], 403);
        }

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

            return $this->json(['data' => $result['data'], 'TotalLignes' => $result['TotalLignes']]);
        }
        else{
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
        $droit = $this->securityRepository->getPermission($user, $this->logger);

        if ((!in_array($droit, [22, 23], true)) || $user->getIdpersonnel() !== $id) {
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
     * @throws Exception
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
    #[OA\Parameter(name: 'IdEquipe', in: 'body', description: 'Salarie ou Interim', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Employé mis à jour avec succès')]
    #[OA\Response(response: 400, description: 'Requête invalide')]
    #[OA\Response(response: 404, description: 'Employé introuvable')]
    public function update(int $id, Request $request, LoggerInterface $logger, #[CurrentUser] Session $user): JsonResponse
    {
        $droit = $this->securityRepository->getPermission($user, $logger);

        if ($droit != 23) {
            throw new AccessDeniedHttpException('Vous n\'avez pas la permission de voir les projets.');
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
            return $this->json([ 'message' => 'Employé introuvable.'], 404);
        }

        return $this->json(['message' => 'Employé mis à jour avec succès']);
    }



}
