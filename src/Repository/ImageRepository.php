<?php

namespace App\Repository;

use App\Entity\Image;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ParameterType;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


class ImageRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Image::class);
    }

    public function getImages(UrlGeneratorInterface $router, int $pageNumber, LoggerInterface $logger, int $limit = 8): array
    {
        try {
            $offset = ($pageNumber - 1) * $limit;


            $sql = '
                SELECT 	CAST(IdPlanningImage AS INT) AS IdPlanningImage
                ,DataPlanningImage
                ,LibellePlanningImage
                ,CAST(\'\' AS XML).value(\'xs:base64Binary(sql:column("DataPlanningImage"))\', \'varchar(max)\') AS DataPlanningB64
                ,COUNT(*) OVER() AS TotalLignes
                FROM (
                    SELECT
                    IdPlanningImage
                    ,LibellePlanningImage
                  ,DataPlanningImage
                    FROM dbo.PlanningImageGandara

                    UNION ALL
                    SELECT
                    IdPlanningImage
                    ,LibellePlanningImage
                    ,DataPlanningImage
                    FROM dbo.PlanningImageClient
                    )AS Datas
                ORDER BY IdPlanningImage DESC
                OFFSET :Offset ROWS FETCH NEXT :Limit ROWS ONLY;
            ';

            $parameters = [
                'Offset' => $offset,
                'Limit'  => $limit,
            ];



            $conn = $this->getEntityManager()->getConnection();
            $images = $conn->fetchAllAssociative($sql, $parameters);

            $struredData = array_map(function($row) use ($router) {
                return [
                    'id' => $row['IdPlanningImage'],
                    // UrlGeneratorInterface::ABSOLUTE_URL force Symfony à inclure http://votredomaine.com
                    'image' => $router->generate('api_serve_image_file', [
                        'id' => $row['IdPlanningImage']
                    ], UrlGeneratorInterface::ABSOLUTE_URL)
                ];
            }, $images);

            return ['image' => $struredData, 'totalLignes' => $images[0]['TotalLignes'] ?? 0];
        }catch (Exception $e) {
            throw new \Exception('An error occurred while retrieving images: ' . $e->getMessage());
        }
    }

    public function getImageById(int $id)
    {
        try {
            $conn = $this->getEntityManager()->getConnection();
            $sql = '
                SELECT DataPlanningImage FROM dbo.PlanningImageGandara WHERE IdPlanningImage = :id
                UNION ALL
                SELECT DataPlanningImage FROM dbo.PlanningImageClient WHERE IdPlanningImage = :id
            ';

            $result = $conn->executeQuery($sql, ['id' => $id])->fetchAssociative();

            // 2. Gestion de l'erreur si l'image n'existe pas
            if (!$result || empty($result['DataPlanningImage'])) {
                throw $this->createNotFoundException('Image introuvable');
            }


            return $result['DataPlanningImage'];

        }catch (Exception $e) {
            throw new \Exception('An error occurred while retrieving the image: ' . $e->getMessage());
        }
    }
}
