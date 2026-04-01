<?php

namespace App\Repository;

use App\Entity\Image;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\Exception;


class ImageRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Image::class);
    }

    public function getImages()
    {
        try {
            $sql = '
                SELECT IdImage,Ink
                FROM Image
            ';

            $conn = $this->getEntityManager()->getConnection();
            $images = $conn->executeQuery($sql)->fetchAllAssociative();

            $struredData = [];
            foreach ($images as $image) {
                $imageBinaire = is_resource($image['Ink']) ? stream_get_contents($image['Ink']) : $image['Ink'];
                $struredData[] = [
                    'id' => $image['IdImage'],
                    'src' => base64_encode($imageBinaire)
                ];
            }

            return $struredData;
        }catch (Exception $e) {
            throw new \Exception('An error occurred while retrieving images: ' . $e->getMessage());
        }
    }
}
