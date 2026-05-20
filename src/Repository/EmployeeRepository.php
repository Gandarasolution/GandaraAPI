<?php

namespace App\Repository;

use App\Entity\Planningevenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

class EmployeeRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlanningEvenement::class);
    }


    /**
     * @throws Exception
     */
    public function getEmployeelist(?int $id = null, ?string $type = null)
    {
        try {
            $conn = $this->getEntityManager()->getConnection();
            $sql = 'EXEC ps_PlanningEmployeeSelect @Id = :Id, @Type = :Type';
            $params = [
                'Id' => $id,
                'Type' => $type
            ];

            $resultSet = $conn->executeQuery($sql, $params)->fetchAllAssociative();

            $structuredData = [];

            foreach ($resultSet as $row) {
                $structuredData[] = [
                    'IdPersonnel' => $row['Id'], // Adapte selon le nom de ton ID
                    'Nom' => $row['Nom'],
                    'Prenom' => $row['Prenom'],
                    'Email' => $row['Email'],
                    'Actif' => $row['Actif'] === 1,
                    'Type' => $row['Type'],
                    'PoleActivite' => $row['IdPoleActivite'],
                    'Equipe' => $row['IdEquipe'],
                ];
            }
            return $structuredData;
        } catch (Exception $e) {
            throw new \Exception('Erreur lors de l\'exécution de la procédure stockée: ' . $e->getMessage());
        }
    }

    public function getEmployeePagination(int $limit, int $pageNumber, string $query, string $codes, LoggerInterface $logger){
        try {
            $conn = $this->getEntityManager()->getConnection();
            $sql = 'EXEC ps_PlanningEmployeeSelectSearch @Limit = :Limit, @PageNumber = :PageNumber, @Query= :Query, @Codes= :Codes';
            $params = [
                'Limit' => $limit,
                'PageNumber' => $pageNumber,
                'Query' => $query,
                'Codes' => $codes
            ];

            $resultSet = $conn->executeQuery($sql, $params)->fetchAllAssociative();

            $structuredData = [];

            $logger->debug("Résultat brut de la procédure stockée", ['resultSet' => $resultSet]);

            foreach ($resultSet as $row) {
                $structuredData[] = [
                    'IdPersonnel' => $row['Id'],
                    'Nom' => $row['Nom'],
                    'Prenom' => $row['Prenom'],
                    'Email' => $row['Email'],
                    'Actif' => $row['Actif'] === 1,
                    'Type' => $row['Type'],
                    'PoleActivite' => $row['IdPoleActivite'],
                    'Equipe' => $row['IdEquipe'],
                ];
            }
            return $structuredData;
        } catch (Exception $e) {
            throw new \Exception('Erreur lors de l\'exécution de la procédure stockée: ' . $e->getMessage());
        }
    }


    public function setEquipeEmployee(int $id, Array $data, LoggerInterface $logger): int
    {
        try {
            $conn = $this->getEntityManager()->getConnection();
            $logger->debug("Appel de la procédure stockée ps_EmployeeUpdateEquipe avec les paramètres", ['Id' => $id, 'Type' => $data['Type'], 'IdEquipe' => $data['IdEquipe']]);
            $sql = 'EXEC ps_EmployeeUpdateEquipe @Id = :Id, @Type = :Type, @IdEquipe = :IdEquipe';
            $params = [
                'Id' => $id,
                'Type' => $data['Type'],
                'IdEquipe' => $data['IdEquipe'],
            ];

            $result = $conn->executeQuery($sql, $params)->fetchAllAssociative();

            return $result[0]['LignesModifiees'];

        } catch (Exception $e) {
            throw new \Exception('Erreur lors de l\'exécution de la procédure stockée: ' . $e->getMessage());
        }
    }

}
