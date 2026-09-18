<?php

namespace App\Repository;

use App\Entity\Poleactivite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\Persistence\ManagerRegistry;

class PoleActiviteRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PoleActivite::class);
    }


    /**
     * @throws Exception
     */
    public function getPoles(int $idPlanningVue) :array {
        $sql = 'EXEC ps_PlanningPoleActiviteSelect @IdPlanningVue = :idPlanningVue';
        $conn = $this->getEntityManager()->getConnection();

        $rows = $conn->fetchAllAssociative($sql, ['idPlanningVue' => $idPlanningVue]);
        $rows[] = [
            'Id' => null,
            'Nom' => 'Sans pôle',
        ];

        return $rows;
    }

}
