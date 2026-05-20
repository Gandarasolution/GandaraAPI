<?php

namespace App\Repository;

use App\Entity\Equipe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EquipeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Equipe::class);
    }

    public function findAll(): array
    {
        $rows  = $this->createQueryBuilder('e')
            ->select(['e.idequipe AS Id', 'e.designationequipe AS Nom'])
            ->getQuery()
            ->getResult();
        $rows[] = [
            'Id' => null,
            'Nom' => 'Sans équipe',
        ];
        return $rows;
    }


}
