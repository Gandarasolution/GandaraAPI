<?php

namespace App\Controller;
use App\Entity\Session;
use App\Repository\EmployeeRepository;
use App\Repository\PlanningTypesJourRepository;
use App\Repository\SecurityRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;


#[Route('/api/planning/day-type')]
#[OA\Tag(name: 'Types de jour')]
class PlanningTypesJourController extends AbstractController
{

    public function __construct(
        private readonly PlanningTypesJourRepository $typesJourRepository,
        private readonly SecurityRepository $securityRepository,
        private readonly LoggerInterface $logger
    ){}


    #[Route('', name: 'api_types_day_list', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Liste des types de jour')]
    #[IsGranted('TYPES_JOUR_VIEW', message: 'Vous n\'avez pas la permission de récupérer les types de jour.')]
    public function list(Request $request, LoggerInterface $logger, #[CurrentUser] Session $user): JsonResponse
    {
        $logger->debug("Récupération de TOUS les types de jour (sans filtres/pagination)");
        $result = $this->typesJourRepository->getAllTypesJour();

        return $this->json(['data' => $result]);

    }

    #[IsGranted('TYPES_JOUR_CREATE', message: 'Vous n\'avez pas la permission d\'ajouter un type de jour.')]
    #[Route('', name: 'api_types_day_add', methods: ['POST'])]
    #[OA\Response(response: 200, description: 'Ajout d\'un type de jour')]
    public function add(Request $request, LoggerInterface $logger, #[CurrentUser] Session $user): JsonResponse
    {

        $data = $request->toArray();


        if (!isset($data['Libelle']) ) {
            return $this->json([
                'message' => 'Les champs "libelle" requis pour ajouter un type de jour.'
            ], 400);
        }

        $logger->debug("Ajout d'un type de jour: {$data['Libelle']}");
        $result = $this->typesJourRepository->addTypeJour($data);

        return $this->json(['data' => $result]);

    }


    #[IsGranted('TYPES_JOUR_EDIT', message: 'Vous n\'avez pas la permission de mettre à jour un type de jour.')]
    #[Route('/{id}', name: 'api_types_day_update', methods: ['PUT'])]
    #[OA\Response(response: 200, description: 'Mise à jour d\'un type de jour')]
    public function update(string $id, Request $request, LoggerInterface $logger, #[CurrentUser] Session $user): JsonResponse
    {
        $data = $request->toArray();



        if (!isset($data['Libelle']) || !isset($data['Id'])) {
            return $this->json([
                'message' => 'Les champs "libelle" et "id" sont requis pour mettre à jour un type de jour.'
            ], 400);
        }

        $result = $this->typesJourRepository->updateTypeJour($data);

        return $this->json(['data' => $result]);

    }


    #[IsGranted('TYPES_JOUR_DELETE', message: 'Vous n\'avez pas la permission de supprimer un type de jour.')]
    #[Route('/{id}', name: 'api_types_day_remove', methods: ['DELETE'])]
    #[OA\Response(response: 200, description: 'Suppression d\'un type de jour')]
    public function remove(string $id, LoggerInterface $logger, #[CurrentUser] Session $user): JsonResponse
    {

        $logger->debug("Suppression d'un type de jour (ID: $id)");
        $result = $this->typesJourRepository->removeTypeJour($id);

        return $this->json(['data' => $result]);

    }


}
