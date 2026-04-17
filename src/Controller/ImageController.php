<?php

namespace App\Controller;

use App\Repository\ImageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/images')]
class ImageController extends AbstractController
{
    public function __construct(
        private ImageRepository $imageRepository,
    ){}

    #[Route('/{id}', name: 'image', methods: ['GET'])]
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

    #[Route('', name: 'list', methods: ['GET'])]
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
