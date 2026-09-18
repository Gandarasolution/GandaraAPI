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

    public function __construct(ManagerRegistry $registry, private UrlGeneratorInterface $router)
    {
        parent::__construct($registry, PlanningEvenement::class);
    }


    /**
     * @throws Exception
     */
    public function getEmployeelist(?int $idPlanningVue, ?int $id = null): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'EXEC ps_PlanningEmployeeSelect @Id = :Id, @IdPlanningVue = :IdPlanningVue';
        $params = [
            'Id' => $id,
            'IdPlanningVue' => $idPlanningVue,
        ];

        $resultSet = $conn->executeQuery($sql, $params)->fetchAllAssociative();

        $structuredData = [];
        $baseImageUrl = $this->router->generate('api_serve_image_file_user', ['id' => 999999], UrlGeneratorInterface::ABSOLUTE_URL);
        $baseImageUrl = str_replace('999999', '', $baseImageUrl);

        foreach ($resultSet as $row) {
            $structuredData[] = [
                'IdPersonnel' => $row['Id'], // Adapte selon le nom de ton ID
                'Nom' => $row['Nom'],
                'Prenom' => $row['Prenom'],
                'Actif' => (int)$row['Actif'] === 1,
                'Type' => $row['Type'],
                'PoleActivite' => $row['IdPoleActivite'],
                'Equipe' => $row['IdEquipe'],
                'Image' => $row['Trombinoscope'] === 1 ? $baseImageUrl . $row['Id'] : null,
            ];
        }
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


}
