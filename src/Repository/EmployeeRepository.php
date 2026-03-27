<?php

namespace App\Repository;

use App\Entity\Planningevenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\Persistence\ManagerRegistry;

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
            $sql = 'EXEC ps_EmployeeSelect @Id = :id, @Type = :type';
            $params = [
                'id' => $id,
                'type' => $type
            ];

            $resultSet = $conn->executeQuery($sql, $params)->fetchAllAssociative();

            $structuredData = [];

            foreach ($resultSet as $row) {
                $structuredData[] = [
                    'Id' => $row['Id'], // Adapte selon le nom de ton ID
                    'Nom' => $row['Nom'],
                    'Prenom' => $row['Prenom'],
                    'Email' => $row['Email'],
                    'Actif' => $row['Actif'],
                    'Type' => $row['Type'],
                    'PoleActivite' => [
                        'Id' => $row['IdPoleActivite'],
                        'Nom' => $row['DesignationPoleActivite']
                    ],
                    'Equipe' => [
                        'Id' => $row['IdEquipe'],
                        'Nom' => $row['DesignationEquipe']
                    ]
                ];
            }
            return $structuredData;
        } catch (Exception $e) {
            throw new \Exception('Erreur lors de l\'exécution de la procédure stockée: ' . $e->getMessage());
        }
    }


    public function setEquipeEployee(int $id, Array $data): int
    {
        try {
            $conn = $this->getEntityManager()->getConnection();
            $sql = 'EXEC ps_EmployeeSetEquipe @Id = :id, @Type = :type, @IdEquipe = :idEquipe, @IdPoleActive = :idPoleActive';
            $params = [
                'id' => $id,
                'type' => $data['Type'],
                'idEquipe' => $data['IdEquipe'],
                'idPoleActive' => $data['IdPoleActivite']

            ];

            return $conn->executeStatement($sql, $params);

        } catch (Exception $e) {
            throw new \Exception('Erreur lors de l\'exécution de la procédure stockée: ' . $e->getMessage());
        }
    }

}
