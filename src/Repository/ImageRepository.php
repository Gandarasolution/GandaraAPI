<?php

namespace App\Repository;

use App\Entity\Image;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\Exception;
use PDO;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


class ImageRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry, private UrlGeneratorInterface $router, private LoggerInterface $logger)
    {
        parent::__construct($registry, Image::class);
    }

    /**
     * @throws Exception
     */
    public function getImages(int $pageNumber, LoggerInterface $logger, int $limit = 8): array
    {
        $offset = ($pageNumber - 1) * $limit;


        $sql = '
                SELECT 	CAST(IdPlanningImage AS INT) AS IdPlanningImage
                ,COUNT(*) OVER() AS TotalLignes
                FROM (
                    SELECT
                    IdPlanningImage
                    FROM dbo.PlanningImageGandara

                    UNION ALL
                    SELECT
                    IdPlanningImage
                    FROM dbo.PlanningImageClient
                    )AS Datas
                ORDER BY IdPlanningImage DESC
                OFFSET :Offset ROWS FETCH NEXT :Limit ROWS ONLY;
            ';

        $parameters = [
            'Offset' => $offset,
            'Limit' => $limit,
        ];


        $conn = $this->getEntityManager()->getConnection();
        $images = $conn->fetchAllAssociative($sql, $parameters);

        $baseImageUrl = $this->router->generate('api_serve_image_file', ['id' => 999999], UrlGeneratorInterface::ABSOLUTE_URL);
        $baseImageUrl = str_replace('999999', '', $baseImageUrl);

        $struredData = array_map(function ($row) use ($baseImageUrl) {
            return [
                'id' => $row['IdPlanningImage'],

                'image' => $baseImageUrl . $row['IdPlanningImage'],
            ];
        }, $images);

        return [
            'image' => $struredData,
            'totalLignes' =>
                isset($images[0]['TotalLignes'])
                    ? (int) $images[0]['TotalLignes']
                    : 0
        ];
    }

    /**
     * @throws Exception
     */
    public function getImageById(int $id)
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = '
            SELECT DataPlanningImage FROM dbo.PlanningImageGandara WHERE IdPlanningImage = :id
            UNION ALL
            SELECT DataPlanningImage FROM dbo.PlanningImageClient WHERE IdPlanningImage = :id
        ';

        $result = $conn->executeQuery($sql, ['id' => $id])->fetchAssociative();

        // 2. Gestion de l'erreur si l'image n'existe pas
        if (!$result || empty($result['DataPlanningImage'])) {
            return null;
        }

        return $result['DataPlanningImage'];
    }

    /**
     * @throws Exception
     */
    public function getImageByIdUser(int $id)
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'EXEC ps_PlanningImageTrombinoscopeSelect  @IdPersonnel = :id';

        $result = $conn->executeQuery($sql, ['id' => $id])->fetchAssociative();

        if (!$result || empty($result['DataImage'])) {
            return null;
        }

        return $result['DataImage'];

    }

    /**
     * Appelle la procédure stockée pour insérer l'image
     * @throws Exception
     */ public function insertImageProcedure(string $pngBinaryData, ?string $libelleImage = null): int
{
    $conn = $this->getEntityManager()->getConnection();

    $sql = 'EXEC ps_PlanningImageInsertUpdateDelete @DataB64 = :imageData, @LibelleImage = :libelleImage';



    $id = $conn->executeQuery($sql, ['imageData' => $pngBinaryData, 'libelleImage' => $libelleImage])->fetchOne();


    $this->logger->debug(sprintf('Image insérée avec l\'ID : %s', $id));

    if (!$id) {
        throw new \RuntimeException('Erreur lors de l\'insertion de l\'image.');
    }

    return $id;

}
}

