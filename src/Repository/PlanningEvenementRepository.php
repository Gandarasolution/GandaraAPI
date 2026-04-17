<?php

namespace App\Repository;

use App\Entity\Planningevenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

/**
 * @extends ServiceEntityRepository<Planningevenement>
 */
class PlanningEvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Planningevenement::class);
    }

    private function structuredData(array $data){
        $structuredData = [];
        foreach($data as $row){
            $structuredData[]= [
                'IdPlanningEvenement' => $row['IdPlanningEvenement'],
                'DebutPlanningEvenement' => (int)$row['DebutPlanningEvenement'],
                'FinPlanningEvenement' => (int)$row['FinPlanningEvenement'],
                'AnnotationPlanningEvenement' => $row['AnnotationPlanningEvenement'],
                'Etiquette' => [
                    'IdPlanningEtiquette' => $row['IdPlanningEtiquette'],
                    'LibelleLongPlanningEtiquette' => $row['LibelleLongPlanningEtiquette'],
                    'LibelleCourtPlanningEtiquette' => $row['LibelleCourtPlanningEtiquette']
                ],
                'Type' => $row['Type'],
                'Ressource' => [
                    'IdPlanningRessource' => $row['IdPlanningRessource'],
                    'LibellePlanningRessource' => $row['Libelle'],
                    'CodePlanningRessource' => $row['Code'],
                    'ChargeAffaire' => $row['ChargeAffaire'],
                    'IdImage' => $row['IdImage'],
                    'CouleurBordurePlanningRessource' => $row['CouleurBordurePlanningRessource'],
                    'CouleurFondPlanningRessource' => $row['CouleurFondPlanningRessource'],
                    'CouleurTextePlanningRessource' => $row['CouleurTextePlanningRessource'],
                    'Actif' => $row['Actif'] === 1,
                ],
                'Employee' => [
                    'IdPersonnel' => $row['IdEmployee'],
                    'Nom' => $row['EmployeeNom'],
                    'Prenom' => $row['EmployeePrenom'],
                    'Type' => $row['TypeEmployee'],
                    'Actif' => $row['Actif'] === 1,
                ]
            ];
        }

        return $structuredData;
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


            return $this->structuredData($result);

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

            return $this->structuredData($result);
        }catch (Exception $e) {
            throw new \Exception('Erreur lors de l\'exécution de la procédure stockée: ' . $e->getMessage());
        }

    }

    public function findEventsByEmployee(int $employeeId, string $type)
    {
        try {
            $conn = $this->getEntityManager()->getConnection();
            $sql = 'EXEC ps_PlanningEvenementSelect @IdEmploye = :IdEmployee, @Type = :Type';
            $params = [
                'IdEmployee' => $employeeId,
                'Type' => $type
            ];
            $result = $conn->executeQuery($sql, $params)->fetchAllAssociative();

            return $this->structuredData($result);

        } catch (Exception $e) {
            throw new \Exception('Erreur lors de l\'exécution de la procédure stockée: ' . $e->getMessage());
        }
    }

    public function createEvent(array $data, LoggerInterface $logger)
    {
        try {
            $debutObj = new \DateTime()->setTimestamp((int)($data['DebutPlanningEvenement'] / 1000));
            $finObj   = new \DateTime()->setTimestamp((int)($data['FinPlanningEvenement'] / 1000));

            $conn = $this->getEntityManager()->getConnection();
            $sql = 'EXEC ps_PlanningEvenementInsert @IdEmploye = :IdEmploye, @Type = :Type, @DebutPlanningEvenement = :DebutPlanningEvenement, @FinPlanningEvenement = :FinPlanningEvenement, @AnnotationPlanningEvenement = :AnnotationPlanningEvenement, @IdPlanningRessource = :IdPlanningRessource, @IdPlanningEtiquette = :IdPlanningEtiquette';
            $params = [
                'IdEmploye' => $data['IdEmploye'],
                'Type' => $data['Type'],
                'DebutPlanningEvenement' => $debutObj->format('Y-m-d\TH:i:s'),
                'FinPlanningEvenement' => $finObj->format('Y-m-d\TH:i:s'),
                'AnnotationPlanningEvenement' => $data['AnnotationPlanningEvenement'] ?? null,
                'IdPlanningRessource' => $data['IdPlanningRessource'],
                'IdPlanningEtiquette' => $data['IdPlanningEtiquette'] ?? null
            ];

            $logger->debug('Executing SQL: ' . $sql . ' with params: ' . json_encode($params));
            $result = $conn->executeQuery($sql, $params)->fetchAllAssociative();

            if (!$result) {
                throw new \Exception("Erreur : l'événement n'a pas pu être créé.");
            }
            return $this->structuredData($result);
        } catch (Exception $e) {
            throw new \Exception('Erreur lors de l\'exécution de la procédure stockée: ' . $e->getMessage());
        }
    }

    public function updateEvent(int $id, array $data)
    {
        try{

            $debutObj = new \DateTime()->setTimestamp((int)($data['DebutPlanningEvenement'] / 1000));
            $finObj   = new \DateTime()->setTimestamp((int)($data['FinPlanningEvenement'] / 1000));


            $conn = $this->getEntityManager()->getConnection();
            $sql = 'EXEC ps_PlanningEvenementUpdate @IdEvenement = :IdEvenement,@IdEmploye = :IdEmploye, @Type = :Type, @DebutPlanningEvenement = :DebutPlanningEvenement, @FinPlanningEvenement = :FinPlanningEvenement, @AnnotationPlanningEvenement = :AnnotationPlanningEvenement, @IdPlanningRessource = :IdPlanningRessource, @IdPlanningEtiquette = :IdPlanningEtiquette, @Priorite = :Priorite';
            $params = [
                'IdEvenement' => $id,
                'IdEmploye' => $data['IdEmploye'] ?? null,
                'Type' => $data['Type'] ?? null,
                'DebutPlanningEvenement' => $debutObj->format('Y-m-d\TH:i:s'),
                'FinPlanningEvenement' => $finObj->format('Y-m-d\TH:i:s'),
                'AnnotationPlanningEvenement' => $data['AnnotationPlanningEvenement'] ?? null,
                'IdPlanningRessource' => $data['IdPlanningRessource'] ?? ($data['Ressource']['IdPlanningRessource'] ?? null) ?? null,
                'IdPlanningEtiquette' => $data['IdPlanningEtiquette'] ?? null,
                'Priorite' => $data['PlanningEvenementPriorite']
            ];
            $result = $conn->executeQuery($sql, $params)->fetchAllAssociative();

            return $result[0]['LignesModifiees'];

        }catch (Exception $e) {
            throw new \Exception('Erreur lors de l\'exécution de la procédure stockée: ' . $e->getMessage());
        }
    }

    public function deleteEvent(int $id)
    {
        try {
            $conn = $this->getEntityManager()->getConnection();
            $sql = 'EXEC ps_PlanningEvenementDelete @IdEvenement = :IdEvenement';
            $params = [
                'IdEvenement' => $id,
            ];
            $result = $conn->executeQuery($sql, $params)->fetchAllAssociative();

            return $result[0]['LignesSupprimees'];
        } catch (Exception $e) {
            throw new \Exception('Erreur lors de l\'exécution de la procédure stockée: ' . $e->getMessage());
        }
    }
}
