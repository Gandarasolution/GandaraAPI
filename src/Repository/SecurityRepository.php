<?php

namespace App\Repository;

use App\Entity\Session;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

class SecurityRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Session::class);
    }

    public function me(Session $user,LoggerInterface $logger): array
    {
        try {
            $conn = $this->getEntityManager()->getConnection();

            $sqlEmploye = '
                SELECT
                    COALESCE(S.NomSalarie, I.NomInterim) as NomEmploye,
                    COALESCE(S.PrenomSalarie, I.PrenomInterim) as PrenomEmployee
                FROM SESSION
                LEFT JOIN Salarie S ON S.IdSalarie = IdPersonnel
                LEFT JOIN Interim I ON I.IdInterim = IdPersonnel
                WHERE IdPersonnel = :id
            ';
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

            return [
                'user' => [
                    'IdPersonnel' => $user->getIdpersonnel(),
                    'Nom'         => $employeInfos ? $employeInfos['NomEmploye'] : null,
                    'Prenom'      => $employeInfos ? $employeInfos['PrenomEmployee'] : null,
                ],
                'permissions' => $planningDroit ? (int)$planningDroit['IdDroitNiveau'] : 21,
                'planning'    => array_map(function($row) {
                    return [
                        'IdPlanning'  => $row['IdPlanning'],
                        'NomPlanning' => $row['NomPlanning'],
                        'IdPlanningImage' => $row['IdPlanningImage']
                    ];
                }, $planningAffectation),
                'error'       => 0
            ];
        }catch (\Exception $e) {
            $logger->error('Erreur lors de la récupération des informations de l\'utilisateur : ' . $e->getMessage(), [
                'exception' => $e,
                'userId' => $user->getIdpersonnel()
            ]);
            return [
                'error' => 1,
                'message' => 'Erreur lors de la récupération des informations de l\'utilisateur'
            ];
        }
    }


    public function getPermission(Session $user,LoggerInterface $logger): int
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'EXEC ps_PlanningDroitSelect @IdPersonnel = :id';

        try {
            $planningDroit = $conn->fetchAssociative($sql, [
                'id' => $user->getIdpersonnel()
            ]);
        } catch (Exception $e) {
            $logger->error($e->getMessage());
        }

        return $level = (int)$planningDroit['IdDroitNiveau'] ?: 21;

    }

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

        try {
            $employeeData = $conn->fetchAllAssociative($sqlEmployeeDroit);
            $allPermissions = $conn->fetchAllAssociative($sqlListDroit);
        } catch (Exception $e) {
            $logger->error($e->getMessage());
            throw new \Exception('Erreur lors de l\'exécution des requêtes SQL: ' . $e->getMessage());
        }

        $employees = [];
        $permission = [];

        foreach ($employeeData as $row) {
            if (!isset($employees[$row['IdSalarie']])) {
                $employees[$row['IdSalarie']] = [
                    'IdPersonnel' => (int)$row['IdSalarie'],
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
    public function bulkUpdatePermissions(mixed $updates, LoggerInterface $logger): bool
    {
        $conn = $this->getEntityManager()->getConnection();

        try {
            $conn->beginTransaction();

            // Requête appelant ta procédure stockée (Modifie le nom de la PS si nécessaire)
            $sql = 'EXEC ps_PlanningDroitUpdate @IdPersonnel = ?, @IdDroitNiveau = ?';

            // On prépare la requête une seule fois pour de meilleures performances
            $stmt = $conn->prepare($sql);

            foreach ($updates as $update) {
                // Vérification de sécurité anti-triche
                if (!isset($update['IdPersonnel']) || !isset($update['IdDroit'])) {
                    throw new \Exception("Structure des données invalide.");
                }

                $stmt->bindValue(1, (int) $update['IdPersonnel']);
                $stmt->bindValue(2, (int) $update['IdDroit']);

                $stmt->executeStatement();
            }

            // Si on arrive ici sans erreur, on valide TOUTES les modifications d'un coup
            $conn->commit();
            return true;

        } catch (Exception $e) {
            // Si une seule erreur survient, on ANNULE tout ce qui a été fait dans la boucle
            $conn->rollBack();
            $logger->error('Erreur SQL dans bulkUpdatePermissions : ' . $e->getMessage());

            return false;
        }
    }
}
