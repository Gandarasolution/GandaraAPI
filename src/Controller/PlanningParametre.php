<?php

namespace App\Controller;

use App\Entity\Session;
use App\Repository\ImageRepository;
use App\Repository\PlanningParametreRespository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;


#[Route('/demo/parametres')]
#[OA\Tag(name: 'Parametres')]
class PlanningParametre extends AbstractController
{

    public function __construct(
        private PlanningParametreRespository $planningParametreRespository,
        private LoggerInterface $logger,
    ){}




    #[Route('/{listeIdParametre}', name: 'api_parametres', methods: ['GET'])]
    #[OA\Parameter(name: 'listeIdParametre', in: 'path', description: 'Liste des id de chaque paramètre', schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Liste des paramètres correspondants')]
    #[OA\Response(response: 500, description: 'Erreur interne du serveur')]
    #[OA\Response(response: 400, description: 'La liste des paramètres est vide')]
    public function getParametres(string $listeIdParametre, #[CurrentUser] ?Session $user): JsonResponse
    {

        if (empty($listeIdParametre)) {
            return $this->json([
                'success' => false,
                'message' => 'La liste des paramètres est vide',
            ], 400);
        }

        $this->logger->debug(sprintf('Récupération des paramètres pour l\'utilisateur %s avec la liste d\'ID : %s', $user->getIdpersonnel(), $listeIdParametre));
        $result = $this->planningParametreRespository->getParametres($listeIdParametre, $user->getIdpersonnel());

        return $this->json([
            'success' => true,
            'data' => $result,
        ]);
    }



    #[Route('/{idParametre}', name: 'api_parametres_set', methods: ['PUT'])]
    #[OA\Parameter(name: 'idParametre', in: 'path', description: 'ID du paramètre à modifier', schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Paramètre modifié avec succès')]
    #[OA\Response(response: 400, description: 'Paramètre invalide')]
    #[OA\Response(response: 404, description: 'Paramètre non trouvé')]
    #[OA\Response(response: 500, description: 'Erreur interne du serveur')]
    public function setParametre(string $idParametre, #[CurrentUser] ?Session $user, Request $request): JsonResponse
    {
        if (empty($idParametre)) {
            return $this->json([
                'success' => false,
                'message' => 'L\'ID du paramètre est vide',
            ], 400);
        }
        $data = $request->toArray();
        $valeurParametre = $data['valeurParametre'] ?? null;


        $result = $this->planningParametreRespository->setParametre($idParametre, $valeurParametre,  $user->getIdpersonnel());

        return $this->json([
            'success' => true,
            'data' => $result,
        ]);
    }

}
