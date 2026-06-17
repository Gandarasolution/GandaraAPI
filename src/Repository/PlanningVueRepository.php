<?php

namespace App\Repository;

use App\Entity\Planningvue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\Exception;
use Psr\Log\LoggerInterface;


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

            $sql = 'EXEC ps_PlanningJourNonTravailleSelect @IdPlanning = :IdPlanning';
            $params = [
                'IdPlanning' => $idPlanning,
            ];
            $result = $conn->executeQuery($sql, $params)->fetchAllAssociative();

            return ['Configs' => $structuredData, 'JoursNonTravailles' => $result];
        } catch (Exception $e) {
            throw new \Exception('Erreur lors de la récupération des configurations pour l\'utilisateur ' . $idSession . ': ' . $e->getMessage());
        }
    }

    public function createNonWorkingDates(array $data, mixed $IdPlanning, LoggerInterface $logger)
    {
        try {

            $date = new \DateTime()->setTimestamp((int)($data['nonWorkingDate'] / 1000));

            $conn = $this->getEntityManager()->getConnection();
            $sql = 'EXEC ps_PlanningJourNonTravailleInsert @IdPlanning = :IdPlanning, @DatePlanningJourNontravaille = :DatePlanningJourNontravaille';
            $params = [
                'IdPlanning' => $IdPlanning,
                'DatePlanningJourNontravaille' => $date,
            ];

            $result = $conn->executeQuery($sql, $params)->fetchAllAssociative()[0];


            if ((int)$result['LignesInserees'] === 0){
                throw new \Exception('Aucun jour non travaillé n\'a été ajouté. Veuillez vérifier les données fournies.');
            }

            $logger->debug(json_encode($result));

            return $result['NouvelId'];
        } catch (Exception $e) {
            throw new \Exception('Une erreur est survenue lors de la création du jour non travaillé: ' . $e->getMessage());
        }
    }

    public function deleteNonWorkingDates(int $idDate)
    {
        try {

            $conn = $this->getEntityManager()->getConnection();
            $sql = 'EXEC ps_PlanningJourNonTravailleDelete @IdDate = :IdDate';
            $params = [
                'IdDate' => $idDate,
            ];

            $result = $conn->executeQuery($sql, $params)->fetchAllAssociative()[0];

            if (['LignesSupprimee'] === 0){
                throw new \Exception('Aucun jour non travaillé n\'a été supprimé. Veuillez vérifier les données fournies.');
            }

            return ['message' => 'Jour non travaillé ajouté avec succès.', 'LignesSupprimee' => $result, 'date' => $result['DatePlanningJourNontravaille']];
        } catch (Exception $e) {
            throw new \Exception('Erreur lors de la suppression d\'un jour non travaillé:: ' . $e->getMessage());
        }
    }
}
