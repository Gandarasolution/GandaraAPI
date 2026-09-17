<?php

namespace App\Controller;

use App\Repository\ImageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/api/images')]
#[OA\Tag(name: 'Images')]
class ImageController extends AbstractController
{
    public function __construct(
        private ImageRepository $imageRepository,
        private LoggerInterface $logger,
    ){}

    #[Route('/{id}', name: 'api_serve_image_file', methods: ['GET'])]
    #[OA\Parameter(name: 'id', in: 'path', description: 'ID de l\'image', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'L\'image demandée')]
    #[OA\Response(response: 404, description: 'Image non trouvée')]
    public function getImage(int $id)
    {

        $image = $this->imageRepository->getImageById($id);

        if (!$image) {
            return $this->json(['message' => 'Image introuvable'], 404);
        }

        $response = new Response($image);

        $response->headers->set('Content-Type', 'image/png');

        $response->headers->set('Cache-Control', 'public, max-age=31536000');

        return $response;

    }

    #[Route('', name: 'image_list', methods: ['GET'])]
    #[OA\Parameter(name: 'limit', in: 'query', description: 'Limite de résultats', schema: new OA\Schema(type: 'integer', default: 20))]
    #[OA\Parameter(name: 'pageNum', in: 'query', description: 'Numéro de page pour la pagination', schema: new OA\Schema(type: 'integer', default: 1))]
    #[OA\Response(response: 200, description: 'Toutes les images listées')]
    public function list(Request $request, UrlGeneratorInterface $router) : JsonResponse
    {

        $limit = $request->query->get('limit', 20);
        $pageNumber = $request->query->get('pageNum', 1);

        $result = $this->imageRepository->getImages($router, $pageNumber, $this->logger, $limit);

        return $this->json(['data' => $result]);

    }

    #[Route('/user/{id}', name: 'api_serve_image_file_user', methods: ['GET'])]
    #[OA\Parameter(name: 'id', in: 'path', description: 'ID du salarie/intérimaire', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'L\'image demandée')]
    #[OA\Response(response: 404, description: 'Image non trouvée')]
    public function getImageUser(int $id)
    {

        $image = $this->imageRepository->getImageByIdUser($id);

        if (!$image) {
            return $this->json(['message' => 'Image introuvable'], 404);
        }

        $response = new Response($image);

        $response->headers->set('Content-Type', 'image/png');

        $response->headers->set('Cache-Control', 'public, max-age=31536000');

        return $response;

    }

}
