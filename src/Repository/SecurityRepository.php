<?php

namespace App\Repository;

use App\Entity\Session;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

class SecurityRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Session::class);
    }

    public function me(Session $user,LoggerInterface $logger){
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


}
