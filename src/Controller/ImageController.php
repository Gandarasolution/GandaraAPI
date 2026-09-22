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
        // 0. Récupération de l'image Base64 depuis le corps de la requête (JSON)
        $payload = $request->toArray();
        $base64Input = $payload['image'] ?? null;

        if (!$base64Input) {
            return $this->json(['message' => 'Aucune image fournie.'], 400);
        }

        // 1. Nettoyage de l'en-tête potentiel (ex: "data:image/jpeg;base64,") et décodage
        $base64Clean = preg_replace('#^data:image/\w+;base64,#i', '', $base64Input);
        $binaryData = base64_decode($base64Clean);

        if ($binaryData === false) {
            return $this->json(['message' => 'Encodage Base64 invalide.'], 400);
        }

        // 2. Vérification du Poids (ex: Max 5 Mo)
        // strlen() compte le nombre d'octets de la chaîne binaire décodée
        $maxWeight = 5 * 1024 * 1024;
        if (strlen($binaryData) > $maxWeight) {
            return $this->json(['message' => 'L\'image est trop lourde. Maximum 5 Mo.'], 400);
        }

        // 3. Vérification de la Taille (Dimensions) en mémoire
        $imageInfo = getimagesizefromstring($binaryData);
        if ($imageInfo === false) {
            return $this->json(['message' => 'Le format n\'est pas une image valide.'], 400);
        }

        $width = $imageInfo[0];
        $height = $imageInfo[1];

        if ($width < 100 || $height < 100 || $width > 500 || $height > 500) {
            return $this->json(['message' => 'Dimensions invalides (doit être entre 100x100 et 500x500).'], 400);
        }

        // 4. Lecture de l'image (imagecreatefromstring gère automatiquement JPEG, PNG, WEBP...)
        $gdImage = imagecreatefromstring($binaryData);

        if (!$gdImage) {
            return $this->json(['message' => 'Format d\'image non supporté par le serveur.'], 400);
        }

        // 5. Conversion obligatoire en PNG puis ré-encodage en Base64 "propre"
        ob_start();
        imagepng($gdImage, null, -1);
        $pngBinaryData = ob_get_clean();
        imagedestroy($gdImage); // Libère la mémoire

        // On encode le binaire PNG validé en Base64 pour l'envoi à la BDD
        $finalBase64Image = base64_encode($pngBinaryData);

        // 6. Enregistrement en base via Procédure Stockée
        $id = $imageRepository->insertImageProcedure($finalBase64Image);

        return $this->json([
            'message' => 'Image vérifiée, convertie en PNG et sauvegardée avec succès !',
            'id' => $id
        ], 201);
    }

}
