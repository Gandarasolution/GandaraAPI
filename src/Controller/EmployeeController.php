<?php

namespace App\Controller;

use App\Repository\EmployeeRepository;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use \Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/employees')]
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
    public function list(){
        try {
            $employees = $this->employeeRepository->getEmployeelist();
            return $this->json($employees);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de la récupération des employés: ' . $e->getMessage()], 500);
        }
    }

    //GET /api/employees/:id- Récupérer un employé
    #[Route('/{id}', name: 'api_employees_show', methods: ['GET'])]
    public function getEmployee(int $id, Request $request){
        $type = $request->query->get('type');

        if (!$type || !in_array($type, ['Salarie', 'Interim'])) {
            return $this->json(['error' => 'Le paramètre ?type=Salarie ou ?type=Interim est obligatoire'], 400);
        }

        try {
            // Appel avec paramètres => La PS renvoie une seule ligne (ou vide)
            $result = $this->employeeRepository->getEmployeelist($id, $type);

            if (empty($result)) {
                return $this->json(['error' => 'Employé non trouvé'], 404);
            }

            return $this->json($result[0]);

        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    //PUT /api/employees/:id- Modifier un employé
    #[Route('{id}', name: 'api_employees_update', methods: ['PUT'])]
    public function update(int $id){
        $employee = $this->employeeRepository->find($id);
        if (!$employee) {
            return $this->json(['error' => 'Employé non trouvé'], 404);
        }

        //A compléter une fois le lien avec les événements mis en place

        return $this->json($employee);
    }



}
