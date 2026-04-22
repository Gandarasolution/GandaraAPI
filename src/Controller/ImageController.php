<?php

namespace App\Controller;

use App\Repository\ImageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;

#[Route('/api/images')]
#[OA\Tag(name: 'Images')]
class ImageController extends AbstractController
{
    public function __construct(
        private ImageRepository $imageRepository,
    ){}

    #[Route('/{id}', name: 'image', methods: ['GET'])]
    #[OA\Parameter(name: 'id', in: 'path', description: 'ID de l\'image', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'L\'image demandée')]
    #[OA\Response(response: 404, description: 'Image non trouvée')]
    public function getImage(int $id)
    {
        try {
            $image = $this->imageRepository->getImageById($id);

            if (!$image) {
                return $this->json(['error' => 'Image not found'], 404);
            }

            return $this->json(['error' => 0, 'data' => $image]);
        }catch (\Exception $e) {
            return $this->json(['error'=> 1, 'message' => 'An error occurred while retrieving the image: ' . $e->getMessage()], 500);
        }
    }

    #[Route('', name: 'image_list', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Toutes les images listées')]
    public function list()
    {
        try {
            $images = $this->imageRepository->getImages();

            return $this->json(['error' => 0, 'images' => $images]);
        } catch (\Exception $e) {
            return $this->json(['error' => 1, 'message' => 'An error occurred while retrieving images: ' . $e->getMessage()], 500);
        }
    }
}
