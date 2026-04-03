<?php

namespace App\Repository;

use App\Entity\Planningevenement;
use App\Entity\Planningressource;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\Exception;


class PlanningRessourceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Planningressource::class);
    }

    public function getRessource(mixed $query, mixed $limit, mixed $types)
    {
        try {
            $conn = $this->getEntityManager()->getConnection();
            $sql = 'EXEC ps_PlanningRessourceSelectSearch @Query = :Query, @Limit = :limit, @Types = :types';
            $params = [
                'Query' => $query,
                'limit' => $limit,
                'types' => $types
            ];

            return $conn->executeQuery($sql, $params)->fetchAllAssociative();

        }catch (Exception $e) {
            throw new \Exception('Erreur lors de l\'exécution de la procédure stockée: ' . $e->getMessage());
        }
    }


}
