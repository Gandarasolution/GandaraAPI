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
            $sql = 'EXEC ps_PlanningEtiquetteInsert @IdRessource = :IdRessource, @LibelleLongPlanningEtiquette = :LibelleLongPlanningEtiquette, @LibelleCourtPlanningEtiquette = :LibelleCourtPlanningEtiquette';
            $params = [
                'IdRessource' => $data['IdPlanningRessource'],
                'LibelleLongPlanningEtiquette' => $data['LibelleLongPlanningEtiquette'] ?? null,
                'LibelleCourtPlanningEtiquette' => $data['LibelleCourtPlanningEtiquette'] ?? null
            ];

            $result = $conn->executeQuery($sql, $params)->fetchAllAssociative();

            if (!empty($result)) {
                return $result[0]['NouvelId'];
            }

            // Si la procédure stockée ne renvoie rien, on peut renvoyer null ou déclencher une erreur
            throw new \Exception("La procédure stockée n'a renvoyé aucun ID.");
        }catch (Exception $e) {
            throw new \Exception('Erreur lors de l\'exécution de la procédure stockée : ' . $e->getMessage());
        }
    }

    public function deleteEtiquette(int $idEtiquette)
    {
        try {
            $conn = $this->getEntityManager()->getConnection();
            $sql = '
                DELETE PlanningEtiquette WHERE IdPlanningEtiquette = :IdPlanningEtiquette
            ';
            $params = [
                'IdPlanningEtiquette' => $idEtiquette
            ];

            $lignesSupprimees = $conn->executeStatement($sql, [
                'IdPlanningEtiquette' => $idEtiquette
            ]);

            // Si c'est > 0, c'est que la ligne existait et a bien été supprimée !
            if ($lignesSupprimees > 0) {
                return true;
            }

            // Si ça retourne 0, la requête a marché, mais l'étiquette n'existait pas
            return false;
        }catch (Exception $e) {
            throw new \Exception('Erreur lors de l\'exécution de la procédure stockée : ' . $e->getMessage());
        }
    }
}
