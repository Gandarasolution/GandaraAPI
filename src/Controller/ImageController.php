<?php

namespace App\Controller;

use App\Repository\ImageRepository;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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
        private UrlGeneratorInterface $router
    ){}

    /**
     * @throws Exception
     */
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

        $response->headers->set('Cache-Control', 'private, max-age=86400');

        return $response;

    }

    /**
     * @throws Exception
     */
    #[Route('', name: 'image_list', methods: ['GET'])]
    #[OA\Parameter(name: 'limit', in: 'query', description: 'Limite de résultats', schema: new OA\Schema(type: 'integer', default: 20))]
    #[OA\Parameter(name: 'pageNum', in: 'query', description: 'Numéro de page pour la pagination', schema: new OA\Schema(type: 'integer', default: 1))]
    #[OA\Response(response: 200, description: 'Toutes les images listées')]
    public function list(Request $request) : JsonResponse
    {

        $limit = max(
            1,
            min($request->query->getInt('limit', 20), 100)
        );

        $pageNumber = max(
            1,
            $request->query->getInt('pageNum', 1)
        );


        $result = $this->imageRepository->getImages($pageNumber, $this->logger, $limit);

        return $this->json(['data' => $result]);

    }

    /**
     * @throws Exception
     */
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

        $response->headers->set('Cache-Control', 'private, max-age=86400');

        return $response;

    }


    /**
     * @throws Exception
     */
    #[Route('/upload', name: 'api_image_upload', methods: ['POST'])]
    public function upload(Request $request, ImageRepository $imageRepository): JsonResponse
    {
        /** @var UploadedFile $file */
        $file = $request->files->get('image');

        if (!$file instanceof UploadedFile) {
            return $this->json(['message' => 'Aucune image fournie.'], 400);
        }

        // 1. Vérification du Poids (ex: Max 5 Mo)
        $maxWeight = 5 * 1024 * 1024;
        if ($file->getSize() > $maxWeight) {
            return $this->json(['message' => 'L\'image est trop lourde. Maximum 5 Mo.'], 400);
        }

        // 2. Vérification de la Taille (Dimensions)
        $imageInfo = getimagesize($file->getPathname());
        if ($imageInfo === false) {
            return $this->json(['message' => 'Le fichier n\'est pas une image valide.'], 400);
        }

        $width = $imageInfo[0];
        $height = $imageInfo[1];
        $mimeType = $imageInfo['mime'];

        if ($width < 100 || $height < 100 || $width > 500 || $height > 500) {
            return $this->json(['message' => 'Dimensions invalides (doit être entre 100x100 et 500x500).'], 400);
        }

        // 3. Lecture de l'image selon son format d'origine
        $gdImage = match ($mimeType) {
            'image/jpeg' => imagecreatefromjpeg($file->getPathname()),
            'image/png'  => imagecreatefrompng($file->getPathname()),
            'image/webp' => imagecreatefromwebp($file->getPathname()),
            default      => false,
        };

        if (!$gdImage) {
            return $this->json(['message' => 'Format d\'image non supporté (JPEG, PNG, WEBP).'], 400);
        }

        // 4. Conversion obligatoire en PNG (Capture dans le buffer de sortie)
        ob_start();
        // Le paramètre -1 laisse la compression par défaut, vous pouvez ajuster de 0 à 9
        imagepng($gdImage, null, -1);
        $pngBinaryData = ob_get_clean();
        imagedestroy($gdImage); // Libère la mémoire

        // 5. Enregistrement en base via Procédure Stockée
        $id = $imageRepository->insertImageProcedure($pngBinaryData);

        $baseImageUrl = $this->router->generate('api_serve_image_file', ['id' => $id], UrlGeneratorInterface::ABSOLUTE_URL);
        return $this->json(['message' => 'Image vérifiée, convertie en PNG et sauvegardée avec succès !', 'image' => ['id' =>  $id, 'image' => $baseImageUrl]], 201);
    }

}
