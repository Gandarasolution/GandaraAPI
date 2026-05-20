<?php

namespace App\Repository;

use App\Entity\Poleactivite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PoleActiviteRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PoleActivite::class);
    }

    public function findAll(): array
    {
        $rows = $this->createQueryBuilder('e')
            ->select(['e.idpoleactivite AS Id', 'e.designationpoleactivite AS Nom'])
            ->getQuery()
            ->getResult();
        $rows[] = [
            'Id' => null,
            'Designationpoleactivite' => 'Sans pôle',
        ];
        return $rows;
    }

}
