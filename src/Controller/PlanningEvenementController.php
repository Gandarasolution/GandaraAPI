<?php

namespace App\Controller;

use App\Entity\Planningevenement;
use App\Repository\PlanningEvenementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/event')]
class PlanningEvenementController extends AbstractController
{
    #[Route('/{dateStart}/{dateEnd}', name: 'api_evenements_index', methods: ['GET'], defaults: ['dateStart' => null, 'dateEnd' => null])]
    public function index(PlanningEvenementRepository $planningEvenementRepository, ?string $dateStart = null, ?string $dateEnd = null): JsonResponse
    {
        if ($dateStart && $dateEnd) {
            $start = new \DateTime($dateStart);
            $end = new \DateTime($dateEnd);
            $evenements = $planningEvenementRepository->findEventsByDate($start, $end);
        } elseif ($dateStart) {
            $start = new \DateTime($dateStart);
            $evenements = $planningEvenementRepository->findEventsByDate($start, $start);
        } else {
            $evenements = $planningEvenementRepository->findAll();
        }

        return $this->json($evenements);
    }
}
