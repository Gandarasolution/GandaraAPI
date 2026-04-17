<?php

namespace App\Repository;

use App\Entity\Planningvue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\Exception;


class PlanningVueRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlanningVue::class);
    }

    public function getConfigUser(int $idSession, int $idPlanning)
    {
        try {

            $conn = $this->getEntityManager()->getConnection();
            $sql = 'EXEC ps_PlanningVueSelect @IdPlanning = :IdPlanning, @IdSession = :IdSession';
            $params = [
                'IdPlanning' => $idPlanning,
                'IdSession' => $idSession,
            ];

            $result = $conn->executeQuery($sql, $params)->fetchAllAssociative();

            $structuredData = [];

            foreach ($result as $row) {
                $structuredData[] = [
                    'IdPlanningVue' => $row['IdPlanningVue'],
                    'DescriptionPlanningVue' => $row['DescriptionPlanningVue'],
                    'LibellePlanningVue' => $row['LibellePlanningVue'],
                    'Group' =>[
                        'ChampsPremierGroupePlanningVue' => $row['ChampsPremierGroupePlanningVue'],
                        'ChampsDeuxiemeGroupePlanningVue' => $row['ChampsDeuxiemeGroupePlanningVue']
                    ],
                    'IdPlanningImage' => $row['IdPlanningImage'],
                ];
            }
            return $structuredData;
        } catch (Exception $e) {
            throw new \Exception('Erreur lors de la récupération des configurations pour l\'utilisateur ' . $idSession . ': ' . $e->getMessage());
        }
    }
}
