<?php

namespace App\Repository;

use App\Entity\Planningevenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EmployeeRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry, private UrlGeneratorInterface $router, private LoggerInterface $logger)
    {
        parent::__construct($registry, PlanningEvenement::class);
    }


    /**
     * @throws Exception
     */
    public function getEmployeelist(?int $idPlanningVue, ?int $id = null): array
    {
        $startTime = microtime(true);

        $conn = $this->getEntityManager()->getConnection();
        $sql = 'EXEC ps_PlanningEmployeeSelect @Id = :Id, @IdPlanningVue = :IdPlanningVue';
        $params = [
            'Id' => $id,
            'IdPlanningVue' => $idPlanningVue,
        ];

        // 1. Mesure du temps d'exécution SQL
        $sqlStartTime = microtime(true);
        $resultSet = $conn->executeQuery($sql, $params)->fetchAllAssociative();
        $sqlDurationMs = round((microtime(true) - $sqlStartTime) * 1000, 2);

        $structuredData = [];
        $baseImageUrl = $this->router->generate('api_serve_image_file_user', ['id' => 999999], UrlGeneratorInterface::ABSOLUTE_URL);
        $baseImageUrl = str_replace('999999', '', $baseImageUrl);

        // 2. Mesure du temps de transformation / boucle
        $loopStartTime = microtime(true);
        foreach ($resultSet as $row) {
            $structuredData[] = [
                'IdPersonnel' => $row['Id'],
                'Nom' => $row['Nom'],
                'Prenom' => $row['Prenom'],
                'Actif' => (int)$row['Actif'] === 1,
                'Type' => $row['Type'],
                'PoleActivite' => $row['IdPoleActivite'],
                'Equipe' => $row['IdEquipe'],
                'Image' => $row['Trombinoscope'] === 1 ? $baseImageUrl . $row['Id'] : null,
            ];
        }
        $loopDurationMs = round((microtime(true) - $loopStartTime) * 1000, 2);

        $totalDurationMs = round((microtime(true) - $startTime) * 1000, 2);

        // 3. Écriture dans le log si le logger est disponible

        $this->logger->info('Performance getEmployeelist', [
            'row_count' => count($resultSet),
            'sql_duration_ms' => $sqlDurationMs,
            'loop_duration_ms' => $loopDurationMs,
            'total_duration_ms' => $totalDurationMs,
            'params' => $params,
        ]);


        return $structuredData;

    }

    /**
     * @throws Exception
     */
    public function getEmployeePagination(int $limit, int $pageNumber, string $query, string $codes, LoggerInterface $logger): array
    {
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

        $baseImageUrl = $this->router->generate('api_serve_image_file_user', ['id' => 999999], UrlGeneratorInterface::ABSOLUTE_URL);
        $baseImageUrl = str_replace('999999', '', $baseImageUrl);

        foreach ($resultSet as $row) {
            $structuredData[] = [
                'IdPersonnel' => $row['Id'],
                'Code' => $row['Code'],
                'Nom' => $row['Nom'],
                'Prenom' => $row['Prenom'],
                'Email' => $row['Email'],
                'Actif' => $row['Actif'] === 1,
                'Type' => $row['Type'],
                'PoleActivite' => $row['IdPoleActivite'],
                'Equipe' => $row['IdEquipe'],
                'Image' => $row['Trombinoscope'] === 1 ? $baseImageUrl . $row['Id'] : null,
            ];
        }
        $ligneTotal = !empty($resultSet) ? (int)$resultSet[0]['TotalLignes'] : 0;

        $logger->debug("Données structurées après transformation", ['structuredData' => $structuredData, 'TotalLignes' => $ligneTotal]);
        return
        [
            'data' => $structuredData,
            'TotalLignes' => $ligneTotal
        ];

    }


    /**
     * @throws Exception
     */
    public function setEquipeEmployee(int $id, Array $data, LoggerInterface $logger): int
    {
        $conn = $this->getEntityManager()->getConnection();
        $logger->debug("Appel de la procédure stockée ps_EmployeeUpdateEquipe avec les paramètres", ['Id' => $id, 'Type' => $data['Type'], 'IdEquipe' => $data['IdEquipe']]);
        $sql = 'EXEC ps_EmployeeUpdateEquipe @Id = :Id, @Type = :Type, @IdEquipe = :IdEquipe';
        $params = [
            'Id' => $id,
            'Type' => $data['Type'],
            'IdEquipe' => $data['IdEquipe'],
        ];

        $result = $conn->executeQuery($sql, $params)->fetchAllAssociative();

        if (empty($result)) {
            $logger->error("La procédure stockée ps_EmployeeUpdateEquipe n'a pas renvoyé de résultat pour l'employé avec l'ID: $id");
            throw new \RuntimeException("Erreur lors de la mise à jour de l'équipe pour l'employé avec l'ID: $id");
        }

        return $result[0]['LignesModifiees'];
    }


    /**
     * @throws Exception
     */
    public function getUsers()
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = 'SELECT V.Id AS IdPersonnel, V.Nom AS Nom
                FROM v_Personnel V
                WHERE Id > 0 AND V.Actif = 1;';
        $result = $conn->executeQuery($sql)->fetchAllAssociative();

        return $result;

    }


}
