<?php

namespace App\Controller;

use App\Repository\FilterConfigRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;


#[Route("/api/filters-options")]
#[OA\Tag(name: 'Filtres dynamiques')]
class FilterConfigController extends AbstractController
{

    public function __construct(
        private FilterConfigRepository $filterConfigRepository,
        //private EntityManagerInterface $entityManager,
    ){}

    #[Route('', name: 'api_data_filter', methods: ['GET'])]
    #[OA\Parameter(name: 'types', in: 'query', description: 'Type de vue', schema: new OA\Schema(type: 'string', default: ''))]
    #[OA\Parameter(name: 'keys', in: 'query', description: 'Clé de chaque filtre voulu', schema: new OA\Schema(type: 'string', default: ''))]
    #[OA\Response(response: 200, description: 'Données des filtre')]
    public function list(Request $request, LoggerInterface $logger){
        try {
            $types = $request->query->get('types', 20);
            $keys = $request->query->get('keys', 1);

            $logger->debug("Récupération des options de filtre", ['types' => $types, 'keys' => $keys]);

            $data = $this->filterConfigRepository->get($types, $keys, $logger);


            return $this->json(['error' => 0, 'data' => $data]);
        } catch (\Exception $e) {
            return $this->json(['error' => 1, 'message' => 'Erreur lors de la récupération des filtre: ' . $e->getMessage()], 500);
        }
    }

}
