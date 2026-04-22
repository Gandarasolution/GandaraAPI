<?php

namespace App\Controller;

use App\Repository\EmployeeRepository;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use \Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;

#[Route('/api/employees')]
#[OA\Tag(name: 'Employés')]
class EmployeeController extends AbstractController
{
    public function __construct(
        private EmployeeRepository $employeeRepository,
        //private EntityManagerInterface $entityManager,
    ){}


    //GET /api/employees- Lister tous les employés

    /**
     * @throws Exception
     */
    #[Route('', name: 'api_employees_list', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Liste de tous les employés (Salariés et Intérimaires)')]
    public function list(){
        try {
            $employees = $this->employeeRepository->getEmployeelist();
            return $this->json(['error' => 0, 'data' => $employees]);
        } catch (\Exception $e) {
            return $this->json(['error' => 1, 'message' => 'Erreur lors de la récupération des employés: ' . $e->getMessage()], 500);
        }
    }

    //GET /api/employees/:id- Récupérer un employé
    #[Route('/{id}', name: 'api_employees_show', methods: ['GET'])]
    #[OA\Parameter(name: 'id', in: 'path', description: 'ID de l\'employé', schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'type', in: 'query', description: 'Salarie ou Interim', schema: new OA\Schema(type: 'string', enum: ['Salarie', 'Interim']))]
    #[OA\Response(response: 200, description: 'Détails d\'un employé spécifique')]
    #[OA\Response(response: 404, description: 'Employé introuvable')]
    public function getEmployee(int $id, Request $request){
        $type = $request->query->get('type');

        if (!$type || !in_array($type, ['Salarie', 'Interim'])) {
            return $this->json(['error' => 1, 'message' => 'Le paramètre ?type=Salarie ou ?type=Interim est obligatoire'], 400);
        }

        try {
            // Appel avec paramètres => La PS renvoie une seule ligne (ou vide)
            $result = $this->employeeRepository->getEmployeelist($id, $type);

            if (empty($result)) {
                return $this->json(['error' => 1, 'message' => 'Employé non trouvé'], 404);
            }

            return $this->json(['error' => 0, 'data' => $result[0]]);

        } catch (\Exception $e) {
            return $this->json(['error' => 1 , 'message' => $e->getMessage()], 500);
        }
    }

    //PUT /api/employees/:id- Modifier un employé
    #[Route('{id}', name: 'api_employees_update', methods: ['PUT'])]
    public function update(int $id, Request $request, EmployeeRepository $employeeRepo){

        try {
            $data = $request->toArray();
        } catch (\Exception $e) {
            return $this->json(['error' => 'Données JSON invalides.'], 400);
        }

        $type = $data['Type'] ?? null;
        if (!$type || !in_array($type, ['Salarie', 'Interim'])) {
            return $this->json(['error' => 'Le champ "type" (Salarie ou Interim) est obligatoire dans le body.'], 400);
        }

        try {
            $lignesModifiees = $employeeRepo->updateEmployeeRaw($id, $type, $data);

            // LOGIQUE MÉTIER (Le "if" qui gère le succès silencieux)
            if ($lignesModifiees === 0) {
                // Pas d'erreur technique, mais l'ID n'existait pas
                return $this->json(['error' => 'Employé introuvable.'], 404);
            }

            return $this->json(['message' => 'Employé mis à jour avec succès'], 204);

        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }

    }



}
