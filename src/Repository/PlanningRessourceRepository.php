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


    public function getRessourceById(int $id)
    {
        try {
            $sql = '
                SELECT *
                FROM PlanningRessource PR
                LEFT JOIN Projet P ON P.IdProjet = PR.IdProjet
                LEFT JOIN PlanningRubriquePersonnalise PRP ON PRP.IdPlanningRubriquePersonnalise = PR.IdRubriquePersonnalise
                LEFT JOIN TypeSocialRubriquePaie T ON T.IdTypeSocialRubriquePaie = PR.IdRubrique
                WHERE IdPlanningRessource = :id
            ';

            $conn = $this->getEntityManager()->getConnection();
            $image = $conn->executeQuery($sql, ['id' => $id])->fetchAssociative();
        }catch (Exception $e) {

        }
    }

}
