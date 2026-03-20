<?php

namespace App\Controller;

use App\Entity\Poleactivite;
use App\Repository\PoleActiviteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/poles')]
class PoleActiviteController extends AbstractController
{

    public function __construct(
        private PoleActiviteRepository $poleActiviteRepository,
        private EntityManagerInterface $entityManager
    )
    {}

    //GET /api/poles- Lister les pôles
    #[Route('', name: 'pole_activite', methods: ['GET'])]
    public function list(){
        return $this->poleActiviteRepository->findAll();
    }

    // POST /api/ poles- Créer un pôle
    #[Route('', name: 'pole_activite', methods: ['POST'])]
    public function create(Request $request){
        try {
            $data = json_decode($request->getContent(), true);
            $poleActivite = new PoleActivite();
            $poleActivite->setDesignationPoleActivite($data['name']);

            $this->entityManager->persist($poleActivite);
            $this->entityManager->flush();

            return $this->json(['message' => 'Pôle d\'activité créé avec succès']);
        }catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de la création du pôle d\'activité: ' . $e->getMessage()], 500);
        }
    }

    //PUT /api/poles/:id- Modifier un pôle
    #[Route('{id}', name: 'pole_activite', methods: ['PUT'])]
    public function update(Request $request, int $id){
        try {
            $poleActivite = $this->poleActiviteRepository->find($id);

            if (!$poleActivite) {
                return $this->json(['error' => 'Pôle d\'activité non trouvé'], 404);
            }

            $data = json_decode($request->getContent(), true);
            $poleActivite->setDesignationPoleActivite($data['name']);
            // A compléter en fonction des champs à modifier
            $this->entityManager->persist($poleActivite);
            $this->entityManager->flush();

            return $this->json(['message' => 'Pôle d\'activité modifié avec succès']);

        }catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de la modification du pôle d\'activité: ' . $e->getMessage()], 500);
        }
    }

}
