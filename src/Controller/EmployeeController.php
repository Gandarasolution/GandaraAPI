<?php

namespace App\Controller;

use App\Entity\Session;
use App\Repository\EmployeeRepository;
use App\Repository\SecurityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use \Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/employees')]
#[OA\Tag(name: 'Employés')]
class EmployeeController extends AbstractController
{
    public function __construct(
        private EmployeeRepository $employeeRepository,
        private SecurityRepository $securityRepository
        //private EntityManagerInterface $entityManager,
    ){}


    //GET /api/employees- Lister tous les employés

    /**
     * @throws Exception
     */
    #[Route('', name: 'api_employees_list', methods: ['GET'])]
    #[OA\Parameter(name: 'limit', in: 'query', description: 'Limite de résultats', schema: new OA\Schema(type: 'integer', default: 20))]
    #[OA\Parameter(name: 'pageNum', in: 'query', description: 'Numéro de page pour la pagination', schema: new OA\Schema(type: 'integer', default: 1))]
    #[OA\Parameter(name: 'q', in: 'query', description: '', schema: new OA\Schema(type: 'string', default: ''))]
    #[OA\Response(response: 200, description: 'Liste de tous les employés (Salariés et Intérimaires)')]
    public function list(Request $request, LoggerInterface $logger, #[CurrentUser] Session $user){
        $droit = $this->securityRepository->getPermission($user, $logger);

        if ($droit != 23) {
            return $this->json([
                'error' => 1,
                'message' => 'Vous n\'avez pas la permission de voir les projets.'
            ], 403);
        }

        try {

            if (empty($request->query->all())) {
                $logger->debug("Récupération de TOUS les employés (sans filtres/pagination)");
                $employees = $this->employeeRepository->getEmployeelist();
                return $this->json(['error' => 0, 'data' => $employees]);
            }
            else{
                $limit = $request->query->get('limit', 20);
                $pageNumber = $request->query->get('pageNum', 1);
                $q = $request->query->get('q', '');
                $codes = $request->query->get('code', '');

                $logger->debug("Récupération de la liste des employés", ['limit' => $limit, 'pageNum' => $pageNumber, 'q' => $q, 'codes' => $codes]);

                $result = $this->employeeRepository->getEmployeePagination($limit, $pageNumber, $q, $codes, $logger);

                return $this->json(['error' => 0, 'data' => $result['data'], 'TotalLignes' => $result['TotalLignes']]);
            }
        } catch (\Exception $e) {
            return $this->json(['error' => 1, 'message' => 'Erreur lors de la récupération des employés: ' . $e->getMessage()], 500);
        }
    }

    //GET /api/employees/:id- Récupérer un employé
    #[Route('/{id}', name: 'api_employees_show', methods: ['GET'])]
    #[OA\Parameter(name: 'id', in: 'path', description: 'ID de l\'employé', schema: new OA\Schema(type: 'integer'))]
   # #[OA\Parameter(name: 'type', in: 'query', description: 'Salarie ou Interim', schema: new OA\Schema(type: 'string', enum: ['Salarie', 'Interim']))]
    #[OA\Response(response: 200, description: 'Détails d\'un employé spécifique')]
    #[OA\Response(response: 404, description: 'Employé introuvable')]
    public function getEmployee(int $id, Request $request){
    /*$type = $request->query->get('type');

    if (!in_array($type, ['Salarie', 'Interim'])) {
        return $this->json(['error' => 1, 'message' => 'Le paramètre ?type=Salarie ou ?type=Interim est obligatoire'], 400);
    }
    */

try {
    // Appel avec paramètres => La PS renvoie une seule ligne (ou vide)
$result = $this->employeeRepository->getEmployeelist($id);

if (empty($result)) {
return $this->json(['error' => 1, 'message' => 'Employé non trouvé'], 404);
}

return $this->json(['error' => 0, 'data' => $result]);

} catch (\Exception $e) {
    return $this->json(['error' => 1 , 'message' => $e->getMessage()], 500);
}
    }

    //PUT /api/employees/équipe/:id- Modifier un employé
    #[Route('/équipe/{id}', name: 'api_employees_update', methods: ['PUT'])]
    #[OA\Parameter(name: 'id', in: 'path', description: 'ID de l\'employé', schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'Type', in: 'body', description: 'Salarie ou Interim', schema: new OA\Schema(type: 'string', enum: ['Salarie', 'Interim']))]
    #[OA\Parameter(name: 'IdEquipe', in: 'body', description: 'Salarie ou Interim', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Employé mis à jour avec succès')]
    #[OA\Response(response: 400, description: 'Requête invalide')]
    #[OA\Response(response: 404, description: 'Employé introuvable')]
    public function update(int $id, Request $request, LoggerInterface $logger, #[CurrentUser] Session $user){
        $droit = $this->securityRepository->getPermission($user, $logger);

        if ($droit != 23) {
            return $this->json([
                'error' => 1,
                'message' => 'Vous n\'avez pas la permission de voir les projets.'
            ], 403);
        }

        try{
            $data = $request->toArray();

            $type = $data['Type'] ?? null;
            if (!$type || !in_array($type, ['SALARIE', 'INTERIM'])) {
                return $this->json(['error' => 1, 'message' => 'Le champ "type" (SALARIE ou INTERIM) est obligatoire dans le body.'], 400);
            }
            $lignesModifiees = $this->employeeRepository->setEquipeEmployee($id, $data, $logger);

            if ($lignesModifiees === 0) {
                return $this->json(['error' => 1, 'message' => 'Employé introuvable.'], 404);
            }

            return $this->json(['error' => 0, 'message' => 'Employé mis à jour avec succès']);

        } catch (\Exception $e) {
            return $this->json(['error' => 1, 'message' => $e->getMessage()], 500);
        }
    }



}
