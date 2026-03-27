<?php

namespace App\Repository;

use App\Entity\Planningevenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Planningevenement>
 */
class PlanningEvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Planningevenement::class);
    }

    /**
     * @return PlanningEvenement[] Returns an array of PlanningEvenement objects
     */
    public function findEventsByDate(\DateTimeInterface $dateStart, \DateTimeInterface $dateEnd ): array
    {
        try {
            $startOfDay = (clone $dateStart)->setTime(0, 0, 0);
            $endOfDay = (clone $dateEnd)->setTime(23, 59, 59);

            $conn = $this->getEntityManager()->getConnection();
            $sql = 'EXEC ps_PlanningEvenementSelect @StartDate = :StartDate, @EndDate = :EndDate';
            $params = [
                'StartDate' => $startOfDay->format('Y-m-d\TH:i:s'),
                'EndDate'   => $endOfDay->format('Y-m-d\TH:i:s'),
            ];

             $result = $conn->executeQuery($sql, $params)->fetchAllAssociative();


            $structuredData = [];


            foreach($result as $row){
                 $structuredData[]= [
                     'IdPlanningEvenement' => $row['IdPlanningEvenement'],
                     'DebutPlanningEvenement' => $row['DebutPlanningEvenement'],
                     'FinPlanningEvenement' => $row['FinPlanningEvenement'],
                     'AnnotationPlanningEvenement' => $row['AnnotationPlanningEvenement'],
                     'IdPlanningRessource' => $row['IdPlanningRessource'],
                     'Etiquette' => [
                         'IdPlanningEtiquette' => $row['IdPlanningEtiquette'],
                         'LibelleLongPlanningEtiquette' => $row['LibelleLongPlanningEtiquette'],
                         'LibelleCourtPlanningEtiquette' => $row['LibelleCourtPlanningEtiquette']
                     ],
                     'Ressource' => [
                         'Libelle' => $row['Libelle'],
                         'Type' => $row['Type'],
                         'Code' => $row['Code'],
                         'ChargeAffaire' => $row['ChargeAffaire'],
                     ]
                 ];
            }

            return $structuredData;

        } catch (Exception $e) {
            throw new \Exception('Erreur lors de l\'exécution de la procédure stockée: ' . $e->getMessage());
        }
    }

    public function findEventById(int $id): array
    {
        try {
            $conn = $this->getEntityManager()->getConnection();
            $sql = 'EXEC ps_PlanningEvenementSelect @Id = :Id';
            $params = [
                'Id' => $id,
            ];
            $result = $conn->executeQuery($sql, $params)->fetchAssociative();

            if (!$result) {
                return [];
            }
            return [
                'IdPlanningEvenement' => $result['IdPlanningEvenement'],
                'DebutPlanningEvenement' => $result['DebutPlanningEvenement'],
                'FinPlanningEvenement' => $result['FinPlanningEvenement'],
                'AnnotationPlanningEvenement' => $result['AnnotationPlanningEvenement'],
                'IdPlanningRessource' => $result['IdPlanningRessource'],
                'Etiquette' => [
                    'IdPlanningEtiquette' => $result['IdPlanningEtiquette'],
                    'LibelleLongPlanningEtiquette' => $result['LibelleLongPlanningEtiquette'],
                    'LibelleCourtPlanningEtiquette' => $result['LibelleCourtPlanningEtiquette']
                ],
                'Ressource' => [
                    'Libelle' => $result['Libelle'],
                    'Type' => $result['Type'],
                    'Code' => $result['Code'],
                    'ChargeAffaire' => $result['ChargeAffaire'],
                ]
            ];
        }catch (Exception $e) {
            throw new \Exception('Erreur lors de l\'exécution de la procédure stockée: ' . $e->getMessage());
        }

    }

    public function findEventsByEmployee(int $employeeId)
    {
    }
}
