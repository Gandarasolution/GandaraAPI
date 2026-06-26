<?php

namespace App\Controller;

use App\Repository\EmployeeRepository;
use App\Repository\PlanningRessourceRepository;
use App\Repository\SecurityRepository;
use App\Service\MercureNotificationService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/permissions')]
#[OA\Tag(name: 'Permissions')]
class PermissionController extends AbstractController
{

    public function __construct(
        private EmployeeRepository $employeeRepository
    )
    {
    }

    #[Route('/', name: 'app_permissions', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Liste des permissions pour utilisateur')]
    public function getPermissions(LoggerInterface $logger){
        try {
            $result = $this->employeeRepository->getPermissions($logger);

            return $this->json([
                'error' => 0,
                'data' => $result
            ]);
        }catch (\Exception $e) {
            return $this->json([
                'error' => 1,
                'message' => $e->getMessage()
            ], 500);
        }
    }

}
