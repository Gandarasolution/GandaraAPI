<?php

namespace App\Repository;

use App\Entity\Image;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ParameterType;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\Exception;


class ImageRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Image::class);
    }

    public function getImages(int $pageNumber, int $limit = 10)
    {
        try {
            $offset = ($pageNumber - 1) * $limit;


            $sql = '
                SELECT IdImage,Ink
                FROM Image
                ORDER BY IdImage DESC
                OFFSET :Offset ROWS FETCH NEXT :Limit ROWS ONLY;
            ';

            $parameters = [
                'Offset' => $offset,
                'Limit'  => $limit,
            ];

            $types = [
                'Offset' => ParameterType::INTEGER,
                'Limit'  => ParameterType::INTEGER,
            ];

            $conn = $this->getEntityManager()->getConnection();
            $images = $conn->executeQuery($sql, $parameters,$types)->fetchAllAssociative();


            $struredData = [];
            foreach ($images as $image) {
                $imageBinaire = is_resource($image['Ink']) ? stream_get_contents($image['Ink']) : $image['Ink'];
                $imageBinairePropre = substr($imageBinaire, 78);
                $base64 = base64_encode($imageBinairePropre);
                $struredData[] = [
                    'id' => $image['IdImage'],
                    'src' => 'data:image/jpeg;base64,' . $base64,
                ];
            }

            return $struredData;
        }catch (Exception $e) {
            throw new \Exception('An error occurred while retrieving images: ' . $e->getMessage());
        }
    }

    public function getImageById(int $id)
    {
        try {
            $sql = '
                SELECT IdImage,Ink
                FROM Image
                WHERE IdImage = :id
            ';

            $conn = $this->getEntityManager()->getConnection();
            $image = $conn->executeQuery($sql, ['id' => $id])->fetchAssociative();

            if (!$image) {
                return null;
            }

            $imageBinaire = is_resource($image['Ink']) ? stream_get_contents($image['Ink']) : $image['Ink'];
            $base64 = base64_encode($imageBinaire);
             return 'data:image/jpeg;base64,' . $base64;
        }catch (Exception $e) {
            throw new \Exception('An error occurred while retrieving the image: ' . $e->getMessage());
        }
    }
}
