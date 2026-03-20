<?php

namespace App\Controller;

use App\Repository\EmployeeRepository;
use Doctrine\ORM\EntityManagerInterface;
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
    #[Route('', name: 'api_employees_index', methods: ['GET'])]
    public function list(){
        $result = $this->employeeRepository->findAll();

        return $this->json($result);
    }

    //GET /api/employees/:id- Récupérer un employé
    #[Route('{id}', name: 'api_employees_index', methods: ['GET'])]
    public function getEmployee(int $id){
        $employee = $this->employeeRepository->find($id);
        if (!$employee) {
            return $this->json(['error' => 'Employé non trouvé'], 404);
        }
        return $this->json($employee);
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
