<?php

namespace App\Repository;

use App\Entity\Session;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SecurityRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry, private UrlGeneratorInterface $router)
    {
        parent::__construct($registry, Session::class);
    }

    public function me(Session $user,LoggerInterface $logger): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sqlEmploye = 'EXEC ps_PlanningSessionInfoSelect @Id = :id';
        $employeInfos = $conn->fetchAssociative($sqlEmploye, ['id' => $user->getIdpersonnel()]);

        $sqlDroit = 'EXEC ps_PlanningDroitSelect @IdPersonnel = :id';
        $planningDroit = $conn->fetchAssociative($sqlDroit, ['id' => $user->getIdpersonnel()]);

        $sqlPlannings = '
            SELECT P.IdPlanning, NomPlanning, IdPlanningImage
            FROM Planning P
            LEFT JOIN PlanningAffectation PA ON P.IdPlanning = PA.IdPlanning
            WHERE IdPersonnel = :id
        ';
        $planningAffectation = $conn->fetchAllAssociative($sqlPlannings, ['id' => $user->getIdpersonnel()]);

        $baseImageUrl = $this->router->generate('api_serve_image_file_user', ['id' => 999999], UrlGeneratorInterface::ABSOLUTE_URL);
        $baseImageUrl = str_replace('999999', '', $baseImageUrl);

        return [
            'user' => [
                'IdPersonnel' => $user->getIdpersonnel(),
                'Nom'         => $employeInfos ? $employeInfos['NomEmploye'] : null,
                'Prenom'      => $employeInfos ? $employeInfos['PrenomEmployee'] : null,
                'Image'       => $employeInfos['Trombinoscope'] === 1 ? $baseImageUrl . $user->getIdpersonnel() : null,
            ],
            'permissions' => $planningDroit ? (int)$planningDroit['IdDroitNiveau'] : 21,
            'planning'    => array_map(function($row) {
                return [
                    'IdPlanning'  => $row['IdPlanning'],
                    'NomPlanning' => $row['NomPlanning'],
                    'IdPlanningImage' => $row['IdPlanningImage']
                ];
            }, $planningAffectation),
        ];

    }


    /**
     * @throws Exception
     */
    public function getPermission(Session $user, LoggerInterface $logger): int
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'EXEC ps_PlanningDroitSelect @IdPersonnel = :id';


        $planningDroit = $conn->fetchAssociative($sql, [
            'id' => $user->getIdpersonnel()
        ]);


        return $level = (int)$planningDroit['IdDroitNiveau'] ?? 21;

    }

    /**
     * @throws Exception
     */
    public function getPermissions(LoggerInterface $logger){
        $conn = $this->getEntityManager()->getConnection();
        $sqlEmployeeDroit = 'EXEC ps_PlanningDroitSelect';
        $sqlListDroit = '
            SELECT
                IdDroitNiveau,
                LibelleDroitNiveau
            FROM ListeDroitNiveau
            WHERE IdDroitNiveau IN (21, 22, 23)
        ';

        $employeeData = $conn->fetchAllAssociative($sqlEmployeeDroit);
        $allPermissions = $conn->fetchAllAssociative($sqlListDroit);


        $employees = [];
        $permission = [];

        foreach ($employeeData as $row) {
            if (!isset($employees[$row['Id']])) {
                $employees[$row['Id']] = [
                    'IdPersonnel' => (int)$row['Id'],
                    'NomPersonnel' => $row['NomPersonnel'],
                    'PrenomPersonnel' => $row['PrenomPersonnel'],
                    'IdDroit' => (int)$row['IdDroitNiveau'],
                ];
            }
        }

        foreach ($allPermissions as $row) {
            if (!isset($permission[$row['IdDroitNiveau']])) {
                $permission[$row['IdDroitNiveau']] = [
                    'IdDroit' => (int)$row['IdDroitNiveau'],
                    'LibelleDroit' => $row['LibelleDroitNiveau'],
                ];
            }
        }

        $employees = array_values($employees);
        $permissions = array_values($permission);

        return ['employees' => $employees, 'permissions' => $permissions];

    }

    /**
     * @throws Exception
     */
    public function bulkUpdatePermissions(mixed $updates, LoggerInterface $logger): void
    {
        $conn = $this->getEntityManager()->getConnection();

        $conn->transactional(function ($conn) use ($updates) {

            $sql = 'EXEC ps_PlanningDroitUpdate @IdPersonnel = ?, @IdDroitNiveau = ?';
            $stmt = $conn->prepare($sql);

            foreach ($updates as $update) {
                if (!isset($update['IdPersonnel']) || !isset($update['IdDroit'])) {
                    throw new \InvalidArgumentException("Structure des données invalide.");
                }

                $stmt->bindValue(1, (int)$update['IdPersonnel']);
                $stmt->bindValue(2, (int)$update['IdDroit']);
                $stmt->executeStatement();
            }
        });
    }
}

