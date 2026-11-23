<?php

namespace App\Controller;

use App\Entity\Session;
use App\Repository\EquipeRepository;
use App\Security\Voter\EquipeVoter;
use Doctrine\DBAL\Exception;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;


#[Route('/demo/equipes')]
#[OA\Tag(name: 'Équipes')]
class PlanningEquipeController extends AbstractController
{

    /**
     * @param EquipeRepository $repository
     */
    public function __construct(
        private readonly EquipeRepository $repository,
        private readonly LoggerInterface $logger,
    ){}


    /**
     * @throws Exception
     */
    #[Route('', name: 'equipe_planning_list', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Liste de toutes les équipes')]
    public function list(Request $request): JsonResponse
    {
        $idPlanningVue = $request->headers->get('X-PlanningVue-Id');


        $equipes = $this->repository->getAllEquipes((int)$idPlanningVue);


        return $this->json(['data' => $equipes]);

    }

    #[Route('/{id}/personnel', name: 'equipe_planning_list_personnel', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted(EquipeVoter::VIEW, message: 'Vous n\'avez pas la permission de récupérer le personnel de cette équipe.')]
    #[OA\Response(response: 200, description: 'Liste de tout le personnel d\'une équipe')]
    #[OA\Parameter(name: 'id', in: 'path', description: 'ID de l\'équipe', schema: new OA\Schema(type: 'integer'))]
    public function listPersonnel(int $id, #[CurrentUser] Session $user): JsonResponse
    {
        $equipes = $this->repository->getPersonnelEquipe($id, $user->getIdpersonnel(), $this->logger);

        return $this->json(['data' => $equipes]);
    }

    #[Route('/{id}/check-delete', name: 'equipe_planning_check_delete', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted(EquipeVoter::DELETE)]
    #[OA\Response(response: 200, description: 'Vérifie si une équipe peut être supprimée')]
    #[OA\Parameter(name: 'id', in: 'path', description: 'ID de l\'équipe', schema: new OA\Schema(type: 'integer'))]
    public function checkDelete(int $id, Request $request, #[CurrentUser] Session $user): JsonResponse
    {
        $result = $this->repository->getDeleteImpact($id);

        if ($result === null) {
            return $this->json([
                'checkRequired' => false,
            ]);
        }

        return $this->json(['checkRequired' => true, 'message' => $result]);
    }

    #[Route('', name: 'equipe_planning_create', methods: ['POST'])]
    #[IsGranted(EquipeVoter::CREATE)]
    #[OA\RequestBody(
        description: 'Les informations pour créer une équipe',
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'Nom', type: 'string', description: 'Le nom de l\'équipe'),
            ],
            type: 'object'
        )
    )]
    #[OA\Response(response: 200, description: 'Équipe créée avec succès')]
    public function create(Request $request, #[CurrentUser] Session $user): JsonResponse
    {
        $data = $request->toArray();
        $designationEquipe = $data['Nom'] ?? null;

        if (!$designationEquipe) {
            return $this->json(['error' => 'Le nom de l\'équipe est requis'], 400);
        }

        $this->repository->createEquipe($designationEquipe, $user->getIdpersonnel(), $this->logger);

        return $this->json(['message' => 'Équipe créée avec succès']);
    }

    #[Route('/{id}', name: 'equipe_planning_update', requirements: ['id' => '\d+'], methods: ['PUT'])]
    #[IsGranted(EquipeVoter::UPDATE)]
    #[OA\Parameter(name: 'id', in: 'path', description: 'ID de l\'équipe', schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(
        description: 'Les nouvelles informations de l\'équipe',
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'Nom', type: 'string', description: 'Le nouveau nom de l\'équipe'),
            ],
            type: 'object'
        )
    )]
    #[OA\Response(response: 200, description: 'Équipe modifiée avec succès')]
    #[OA\Response(response: 404, description: 'Équipe non trouvée')]
    public function update(Request $request, int $id, #[CurrentUser] Session $user): JsonResponse
    {
        $data = $request->toArray();
        $newName = $data['Nom'] ?? null;

        if (!$newName) {
            return $this->json(['error' => 'Le nouveau nom de l\'équipe est requis'], 400);
        }

        $this->repository->updateEquipe($id, $newName, $user->getIdpersonnel(), $this->logger);

        return $this->json(['message' => 'Équipe mise à jour avec succès']);
    }

    #[Route('/{id}', name: 'equipe_planning_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    #[IsGranted(EquipeVoter::DELETE)]
    #[OA\Parameter(name: 'id', in: 'path', description: 'ID de l\'équipe', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Équipe supprimée avec succès')]
    public function delete(int $id, #[CurrentUser] Session $user, Request $request): JsonResponse
    {
        $data = $request->toArray();

        $this->repository->deleteEquipe($id, $data['numberEmployeeCheck'] ?? 0, $user->getIdpersonnel(), $this->logger);

        return $this->json(['message' => 'Équipe supprimée avec succès']);
    }




}
