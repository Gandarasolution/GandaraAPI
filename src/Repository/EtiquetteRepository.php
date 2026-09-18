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

    /**
     * @throws Exception
     */
    public function findEtiquetteByIdRessource(int $idRessource): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'EXEC ps_PlanningEtiquetteSelect @IdRessource = :IdRessource';
        $params = [
            'IdRessource' => $idRessource
        ];

        return $conn->executeQuery($sql, $params)->fetchAllAssociative();
    }

    /**
     * @throws Exception
     */
    public function createEtiquette(array $data) : int
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'EXEC ps_PlanningEtiquetteInsert @IdRessource = :IdRessource, @LibelleLongPlanningEtiquette = :LibelleLongPlanningEtiquette, @LibelleCourtPlanningEtiquette = :LibelleCourtPlanningEtiquette';
        $params = [
            'IdRessource' => $data['IdPlanningRessource'],
            'LibelleLongPlanningEtiquette' => $data['LibelleLongPlanningEtiquette'] ?? null,
            'LibelleCourtPlanningEtiquette' => $data['LibelleCourtPlanningEtiquette'] ?? null
        ];

        $result = $conn->executeQuery($sql, $params)->fetchAllAssociative();

        if (empty($result)) {
            throw new \RuntimeException("La création de l'étiquette a échoué (aucun ID retourné).");
        }

        return (int)$result[0]['NouvelId'];
    }

    /**
     * @throws Exception
     */
    public function deleteEtiquette(int $idEtiquette): bool
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = '
            DELETE PlanningEtiquette WHERE IdPlanningEtiquette = :IdPlanningEtiquette
        ';

        $lignesSupprimees = $conn->executeStatement($sql, [
            'IdPlanningEtiquette' => $idEtiquette
        ]);

        // Si c'est > 0, c'est que la ligne existait et a bien été supprimée !
        if ($lignesSupprimees > 0) {
            return true;
        }

        // Si ça retourne 0, la requête a marché, mais l'étiquette n'existait pas
        return false;
    }
}
