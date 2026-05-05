<?php

namespace App\Repository;
use App\Entity\Planningetiquette;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\Exception;


class EtiquetteRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Planningetiquette::class);

    }
    public function findEtiquetteByIdRessource(int $idRessource): array
    {
        try {
            $conn = $this->getEntityManager()->getConnection();
            $sql = 'EXEC ps_PlanningEtiquetteSelect @IdRessource = :IdRessource';
            $params = [
                'IdRessource' => $idRessource
            ];

            return $conn->executeQuery($sql, $params)->fetchAllAssociative();
        }catch ( Exception $e) {
            throw new \Exception('Erreur lors de l\'exécution de la procédure stockée : ' . $e->getMessage());
        }
    }

    public function createEtiquette(array $data)
    {
        try{
            $conn = $this->getEntityManager()->getConnection();
            $sql = 'EXEC ps_PlanningEtiquetteCreate @IdRessource = :IdRessource, @LibelleLongPlanningEtiquette = :LibelleLongPlanningEtiquette, @LibelleCourtPlanningEtiquette = :LibelleCourtPlanningEtiquette';
            $params = [
                'IdRessource' => $data['IdPlanningRessource'],
                'LibelleLongPlanningEtiquette' => $data['LibelleLongPlanningEtiquette'] ?? null,
                'LibelleCourtPlanningEtiquette' => $data['LibelleCourtPlanningEtiquette' ?? null]
            ];

            return $conn->executeQuery($sql, $params)->fetchAllAssociative()[0]['NouvelId'];

        }catch (Exception $e) {
            throw new \Exception('Erreur lors de l\'exécution de la procédure stockée : ' . $e->getMessage());
        }
    }
}
