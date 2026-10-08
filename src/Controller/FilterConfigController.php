<?php

namespace App\Controller;

use App\Entity\Session;
use App\Repository\FilterConfigRepository;
use Doctrine\DBAL\Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Symfony\Component\Security\Http\Attribute\CurrentUser;



#[Route("/api/filters-options")]
#[OA\Tag(name: 'Filtres dynamiques')]
class FilterConfigController extends AbstractController
{

    public function __construct(
        private FilterConfigRepository $filterConfigRepository,
        //private EntityManagerInterface $entityManager,
    ){}

    /**
     * @throws Exception
     */
    #[Route('', name: 'api_data_filter', methods: ['GET'])]
    #[OA\Parameter(name: 'types', in: 'query', description: 'Type de vue', schema: new OA\Schema(type: 'string', default: ''))]
    #[OA\Parameter(name: 'keys', in: 'query', description: 'Clé de chaque filtre voulu', schema: new OA\Schema(type: 'string', default: ''))]
    #[OA\Response(response: 200, description: 'Données des filtre')]
    public function list(Request $request, LoggerInterface $logger, #[CurrentUser] Session $user){

        $types = $request->query->get('types', 20);
        $keys = $request->query->get('keys', 1);

        $logger->debug("Récupération des options de filtre", ['types' => $types, 'keys' => $keys]);

        $data = $this->filterConfigRepository->get($user->getIdpersonnel(), $types, $keys, $logger);

        return $this->json(['data' => $data]);

    }




    #[Route('', name: 'api_data_filter_save', methods: ['POST'])]
    #[OA\Parameter(name: 'viewType', in: 'body', description: 'Espace de l\'utilisateur', schema: new OA\Schema(type: 'string', default: ''))]
    #[OA\Parameter(name: 'activeFilters', in: 'body', description: 'Filtres actifs', schema: new OA\Schema(type: 'string', default: ''))]
    #[OA\Response(response: 200, description: 'Données des filtre')]
    public function save(Request $request, LoggerInterface $logger, #[CurrentUser] Session $user){

        $data = $request->toArray();
        $viewType = $data['viewType'] ?? '';
        $activeFilters = $data['activeFilters'] ?? '';

        $logger->debug("Sauvegarde des filtres actifs", ['viewType' => $viewType, 'activeFilters' => $activeFilters]);

        $this->filterConfigRepository->save($user->getIdpersonnel(), $viewType, $activeFilters, $logger);

        return $this->json(['message' => 'Filtres sauvegardés avec succès.']);

    }

}
