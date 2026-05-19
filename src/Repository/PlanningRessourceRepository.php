<?php

namespace App\Repository;

use App\Entity\Planningevenement;
use App\Entity\Planningressource;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\Exception;
use Psr\Log\LoggerInterface;


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

            $data = $conn->executeQuery($sql, $params)->fetchAllAssociative();

            $structuredData = [];
            foreach($data as $row) {
                $structuredData[] = [
                    'IdPlanningRessource' => $row['IdPlanningRessource'],
                    'LibellePlanningRessource' => $row['LibellePlanningRessource'],
                    'Type' => $row['Type'],
                    'IdImage' => $row['IdImage'],
                    'Actif' => (int)$row['Actif'] === 1
                ];
            }

            return $structuredData;

        }catch (Exception $e) {
            throw new \Exception('Erreur lors de l\'exécution de la procédure stockée: ' . $e->getMessage());
        }
    }


    public function updateRessource(int $id, mixed $data)
    {
        try {
            $conn = $this->getEntityManager()->getConnection();
            $sql = 'EXEC ps_PlanningRessourceUpdate @IdRessource = :Id, @CouleurFondPlanningRessource = :CouleurFondPlanningRessource, @CouleurBordurePlanningRessource = :CouleurBordurePlanningRessource, @CouleurTextePlanningRessource = :CouleurTextePlanningRessource, @IdImage = :IdImage';
            $params = [
                'Id' => $id,
                'CouleurFondPlanningRessource' => $data['CouleurFondPlanningRessource'] ?? null,
                'CouleurBordurePlanningRessource' => $data['CouleurBordurePlanningRessource'] ?? null,
                'CouleurTextePlanningRessource' => $data['CouleurTextePlanningRessource'] ?? null,
                'IdImage' => $data['IdImage'] ?? null
            ];

            return $conn->executeQuery($sql, $params)->fetchAllAssociative()[0]['LignesModifiees'];
        }catch (Exception $e) {
            throw new \Exception('Erreur lors de l\'exécution de la procédure stockée: ' . $e->getMessage());
        }
    }


    public function getProjet(
        mixed $limit,
        mixed $pageNumber,
        mixed $query,
        string $chargeeAffaires,
        string $chefChantiers,
        string $codes,
        string $etats,
        LoggerInterface $logger
    ){
        try {
            $conn = $this->getEntityManager()->getConnection();
            $sql = 'EXEC ps_PlanningRessourceSelectProjet @Limit= :Limit, @PageNumber= :PageNumber, @Query= :Query, @ChargeeAffaires= :ChargeeAffaires, @ChefChantiers= :ChefChantiers, @Codes= :Codes, @Etats= :Etats';
            $params = [
                'Limit' => $limit ?? 20,
                'PageNumber' => $pageNumber ?? 1,
                'Query' => $query,
                'ChargeeAffaires' => $chargeeAffaires,
                'ChefChantiers' => $chefChantiers,
                'Codes' => $codes,
                'Etats' => $etats
            ];
            $result = $conn->executeQuery($sql,$params)->fetchAllAssociative();

            $structuredData = [];
            foreach($result as $row) {
                $structuredData[] = [
                    'IdPlanningRessource' => $row['IdPlanningRessource'],
                    'LibellePlanningRessource' => $row['LibellePlanningRessource'],
                    'IdImage' => $row['IdImage'],
                    'Actif' => (int)$row['Actif'] === 1,
                    'CodePlanningRessource' => $row['Code'],
                    'PoleActivite' => $row['DesignationPoleActivite'],
                    'ChargeAffaire' => $row['ChargeAffaire'],
                    'ChefChantier' => $row['ChefChantier'],
                    'Etat' => $row['Etat'],
                    'Identifiant' => $row['Identifiant'],
                    'DateOS' => $row['DateOS'] ? (new \DateTime($row['DateOS']))->format('d/m/Y') : null,
                    'DateFin' => $row['DateFin'] ? (new \DateTime($row['DateFin']))->format('d/m/Y') : null,
                    'TM' => $row['TM'],
                    'HR' => $row['HR'],
                    'SH' => $row['SH'],
                    'DPF' => $row['DPF'],
                    'RPF' => $row['RPF'],
                    'AP' => $row['AP'],
                    'SP' => $row['SP'],
                ];
            }

            $ligneTotal = $result[0]['TotalLignes'] ?? 0;

            return
            [
                'data' => $structuredData,
                'TotalLignes' => $ligneTotal
            ];

        }catch (Exception $e) {
            throw new \Exception('Erreur lors de l\'exécution de la procédure stockée: ' . $e->getMessage());
        } catch (\DateMalformedStringException $e) {
            throw new \Exception('Erreur lors du formatage de la date: ' . $e->getMessage());
        }
    }

}
