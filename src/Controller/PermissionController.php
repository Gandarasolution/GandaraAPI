<?php

namespace App\Controller;

use App\Repository\EmployeeRepository;
use App\Repository\PlanningRessourceRepository;
use App\Repository\SecurityRepository;
use App\Service\MercureNotificationService;
use Doctrine\DBAL\Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/permissions')]
#[OA\Tag(name: 'Permissions')]
class PermissionController extends AbstractController
{

    public function __construct(
        private readonly SecurityRepository $securityRepository,
        private readonly LoggerInterface $logger
    )
    {
    }

    /**
     * @throws Exception
     */
    #[Route('/', name: 'app_permissions', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Liste des permissions pour utilisateur')]
    public function getPermissions(): JsonResponse
    {
        $result = $this->securityRepository->getPermissions($this->logger);

        return $this->json(['data' => $result]);
    }

    /**
     * @throws \Throwable
     */
    #[Route('/', name: 'app_permissions_set', methods: ['PUT'])]
    #[IsGranted('MANAGE_PERMISSIONS',  message: 'Vous n\'avez pas la permission de modifier les droits.')]
    public function updatePermissions(Request $request): JsonResponse
    {
        // On décode le JSON reçu depuis React
        $data = json_decode($request->getContent(), true);

        $this->logger->debug('Payload reçue pour updatePermissions : ' . json_encode($data));
        // 1. Vérification que la payload est valide
        if (!isset($data) || !is_array($data) || empty($data)) {
            return $this->json([
                'message' => 'Aucune donnée de permission fournie.'
            ], 400);
        }

        $this->securityRepository->bulkUpdatePermissions($data, $this->logger);

        return $this->json([
            'message' => 'Permissions mises à jour avec succès.'
        ]);
    }

}
