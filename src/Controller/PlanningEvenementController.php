<?php

namespace App\Controller;

use App\Repository\PlanningEvenementRepository;
use \Symfony\Component\HttpFoundation\Request;
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

    //GET /api/event?employee=:id&type=Salarie- RDV par employé
    #[Route('/', name: 'api_evenements_by_employee', methods: ['GET'])]
    public function getEventsByEmployee(Request $request): JsonResponse
    {
        try {
            $employeeId =$request->query->get('employee');
            $type = $request->query->get('type');

            if (!$type || !in_array($type, ['Salarie', 'Interim'])) {
                return $this->json(['error' => 'Le paramètre ?type=Salarie ou ?type=Interim est obligatoire'], 400);
            }

            if (!$employeeId) {
                return $this->json(['error' => 'Le paramètre ?employee=:id est obligatoire'], 400);
            }

            $events = $this->planningEvenementRepository->findEventsByEmployee($employeeId, $type);
            return $this->json($events);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }


    //POST /api/event- Créer un RDV
    #[Route('', name: 'api_evenements_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = $request->toArray();
            // Validation des données d'entrée (simplifiée)
            if (
                empty($data['DebutPlanningEvenement'])
                || empty($data['FinPlanningEvenement'])
                || empty($data['IdPlanningRessource'])
            ) {
                return $this->json(['error' => 1, 'message' => 'Les champs DebutPlanningEvenement, FinPlanningEvenement, IdPlanningRessource  sont obligatoires.'], 400);
            }

            $newEventId = $this->planningEvenementRepository->createEvent($data);

            return $this->json(['error' => 0, 'data' => $newEventId], 201);

        } catch (\Exception $e) {
            return $this->json(['error' => 1,'message' => 'Erreur lors de la création de l\'événement: ' . $e->getMessage()], 500);
        }
    }

    //PUT /api/event/:id- Modifier un RDV (à implémenter)
    #[Route('/{id}', name: 'api_evenements_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
     {
         try {
             $data = $request->toArray();
             if (($data === null) || $data === []) {
                 return $this->json(['error' => 'Données JSON invalides.'], 400);
             }
             $lignesModifiees = $this->planningEvenementRepository->updateEvent($id, $data);

             if ($lignesModifiees === 0) {
                 // Pas d'erreur technique, mais l'ID n'existait pas
                 return $this->json(['error' => 'Événement introuvable.'], 404);
             }

             return $this->json(['message' => 'Événement mis à jour avec succès'], 201);

         } catch (\Exception $e) {
             return $this->json(['error' => 'Erreur lors de la mise à jour de l\'événement: ' . $e->getMessage()], 500);
         }
     }


    //DELETE /api/event/:id- Supprimer un RDV
    #[Route('/{id}', name: 'api_evenements_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        try {
            $lignesSupprimees = $this->planningEvenementRepository->deleteEvent($id);
            if ($lignesSupprimees === 0) {
                return $this->json(['error' => 'Événement introuvable.'], 404);
            }
            return $this->json(['message' => 'Événement supprimé avec succès']);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de la suppression de l\'événement: ' . $e->getMessage()], 500);
        }
    }
}
