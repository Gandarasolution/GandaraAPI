<?php

namespace App\Controller;

use App\Repository\PlanningEvenementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/event')]
class PlanningEvenementController extends AbstractController
{

    public function __construct(
        private PlanningEvenementRepository $planningEvenementRepository,
        //private EntityManagerInterface $entityManager,
    ){}

    //GET /api/event- Lister (avec filtres startDate/endDate
    #[Route('/{dateStart}/{dateEnd}', name: 'api_evenements_index', methods: ['GET'])]
    public function index(\DateTimeInterface $dateStart, \DateTimeInterface $dateEnd): JsonResponse
    {

        try{
            $event = $this->planningEvenementRepository->findEventsByDate($dateStart, $dateEnd);
            return $this->json($event);

        }catch(\Exception $e){
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    //GET /api/event/:id- Récupérer un RDV
    #[Route('/{id}', name: 'api_evenements_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        try{
            $event = $this->planningEvenementRepository->findEventById($id);
            if (!$event) {
                return $this->json(['error' => 'Événement non trouvé'], 404);
            }
            return $this->json($event);

        }catch(\Exception $e){
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    //GET /api/event?employee=:id- RDV par employé
    #[Route('/', name: 'api_evenements_by_employee', methods: ['GET'])]
    public function getEventsByEmployee(int $employeeId): JsonResponse
    {
        try {
            $events = $this->planningEvenementRepository->findEventsByEmployee($employeeId);
            return $this->json($events);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }
}
