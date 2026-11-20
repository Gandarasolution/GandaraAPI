<?php

namespace App\Repository;

use App\Entity\Equipe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EquipeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private UrlGeneratorInterface $router, private readonly Connection $conn)
    {
        parent::__construct($registry, Equipe::class);
    }


    /**
     * @throws Exception
     */
    public function getAllEquipes(int $idPlanningVue): array
    {
        $sql = 'EXEC ps_PlanningEquipeSelect @IdPlanningVue = :idPlanningVue';

        $rows = $this->conn->fetchAllAssociative($sql, ['idPlanningVue' => $idPlanningVue]);
        $rows[] = [
            'Id' => null,
            'Nom' => 'Sans équipe',
        ];

        return $rows;
    }

    /**
     * @throws \RuntimeException
     */
    public function createEquipe(mixed $designationEquipe, int $getIdpersonnel, LoggerInterface $logger)
    {
        $sql = 'EXEC ps_EquipeInsertUpdateDelete
            @IdEquipe = :idEquipe,
            @DesignationEquipe = :nom,
            @IdSalarie = :idSalarie,
            @TagDelete = :tagDelete';

        $this->conn->executeStatement($sql, [
            'idEquipe'  => null,
            'nom'       => $designationEquipe,
            'idSalarie' => null,
            'tagDelete' => 0,
        ]);

        return true;

    }

    public function updateEquipe(int $id, mixed $newName, int $getIdpersonnel, LoggerInterface $logger)
    {
        $sql = 'EXEC ps_EquipeInsertUpdateDelete
            @IdEquipe = :idEquipe,
            @DesignationEquipe = :nom,
            @IdSalarie = :idSalarie,
            @TagDelete = :tagDelete';

        $result = $this->conn->executeQuery($sql, [
            'idEquipe'  => $id,
            'nom'       => $newName,
            'idSalarie' => null,
            'tagDelete' => 0,
        ]);

        if ($result->rowCount() === 0) {
            throw new NotFoundHttpException('Aucune équipe trouvée avec l\'ID spécifié.');
        }

        return true;
    }

    public function deleteEquipe(int $id, int $numberEmployeeCheck, int $idpersonnel, LoggerInterface $logger)
    {
        $sql = '
           SELECT COUNT(Id) AS nombrePersonnel
            FROM v_Personnel
            WHERE IdEquipe = ?
        ';
        $result = $this->conn->fetchAssociative($sql, [$id]);

        $nombrePersonnel = $result['nombrePersonnel'] ?? 0;
        if ($nombrePersonnel > 0 && $nombrePersonnel !== $numberEmployeeCheck) {
            throw new \RuntimeException('Cette équipe contient ' . $nombrePersonnel . ' membres. Pour confirmer la suppression de cette équipe merci de ressaisir le nombre de membres si dessous.');
        }

        $sql = 'EXEC ps_EquipeInsertUpdateDelete
            @IdEquipe = :idEquipe,
            @DesignationEquipe = :nom,
            @IdSalarie = :idSalarie,
            @TagDelete = :tagDelete';

        $result = $this->conn->executeQuery($sql, [
            'idEquipe'  => $id,
            'nom'       => null,
            'idSalarie' => null,
            'tagDelete' => 1,
        ]);

        return true;
    }

    public function getPersonnelEquipe(int $id, int $getIdpersonnel, LoggerInterface $logger)
    {
        $sql = 'EXEC ps_PlanningEquipePersonnelSelect @IdEquipe = :idEquipe';

        $resultSet = $this->conn->fetchAllAssociative($sql, [
            'idEquipe'  => $id,
        ]);

        //$baseImageUrl = $this->router->generate('api_serve_image_file_user', ['id' => 999999], UrlGeneratorInterface::ABSOLUTE_URL);
        $baseImageUrl = 'https://gandara-scheduler.vercel.app/demo/images/user/999999';
        $baseImageUrl = str_replace('999999', '', $baseImageUrl);

        $structuredData = [];
        // 2. Mesure du temps de transformation / boucle
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

        return $structuredData;
    }

    public function getDeleteImpact(int $id)
    {
        $sql = '
           SELECT COUNT(Id) AS nombrePersonnel
            FROM v_Personnel
            WHERE IdEquipe = ?
        ';
        $result = $this->conn->fetchAssociative($sql, [$id]);

        $nombrePersonnel = $result['nombrePersonnel'] ?? 0;
        if ($nombrePersonnel > 0) {
            return 'Cette équipe contient ' . $nombrePersonnel . ' membres. Pour confirmer la suppression de cette équipe merci de ressaisir le nombre de membres si dessous.';
        }
        return null;
    }


}
