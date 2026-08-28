<?php

namespace App\Repository;

use App\Entity\Equipe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\Persistence\ManagerRegistry;

class EquipeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Equipe::class);
    }


    public function getAllEquipes(int $idPlanningVue)
    {
        $sql = 'EXEC ps_PlanningEquipeSelect @IdPlanningVue = :idPlanningVue';
        $conn = $this->getEntityManager()->getConnection();

        try {
            $rows = $conn->fetchAllAssociative($sql, ['idPlanningVue' => $idPlanningVue]);
            $rows[] = [
                'Id' => null,
                'Nom' => 'Sans équipe',
            ];

            return $rows;
        }catch (Exception $e){
            throw new \Exception('Erreur lors de la récupération des équipes : ' . $e->getMessage());
        }
    }




}
