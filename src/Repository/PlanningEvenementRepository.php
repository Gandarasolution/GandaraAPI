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

    private function structuredData(array $data): array
    {
        $appointments = [];
        $ressources = [];
        foreach($data as $row){
            $appointments[]= [
                'IdPlanningEvenement' => (int)$row['IdPlanningEvenement'],
                'DebutPlanningEvenement' => (int)$row['DebutPlanningEvenement'],
                'FinPlanningEvenement' => (int)$row['FinPlanningEvenement'],
                'AnnotationPlanningEvenement' => $row['AnnotationPlanningEvenement'],
                'Etiquette' => [
                    'IdPlanningEtiquette' => $row['IdPlanningEtiquette'] ?? null,
                    'LibelleLongPlanningEtiquette' => $row['LibelleLongPlanningEtiquette'],
                    'LibelleCourtPlanningEtiquette' => $row['LibelleCourtPlanningEtiquette']
                ],
                'IdPlanningRessource' => (int)$row['IdPlanningRessource'],
                'IdEmploye' => (int)$row['IdEmployee'],
                'PlanningEvenementPriorite' => (int)$row['PlanningEvenementPriorite']
            ];

            $idRessource = (int)$row['IdPlanningRessource'];

            if (!isset($ressources[$idRessource])) {
                $ressources[$idRessource] = [
                    'IdPlanningRessource'             => $idRessource,
                    'LibellePlanningRessource'        => $row['Libelle'],
                    'CodePlanningRessource'           => $row['Code'],
                    'ChargeAffaire'                   => $row['ChargeAffaire'],
                    'IdImage'                         => (int)$row['IdImage'],
                    'CouleurBordurePlanningRessource' => $row['CouleurBordurePlanningRessource'],
                    'CouleurFondPlanningRessource'    => $row['CouleurFondPlanningRessource'],
                    'CouleurTextePlanningRessource'   => $row['CouleurTextePlanningRessource'],
                    'Actif'                           => (int)$row['Actif'] === 1,
                    'Type'                            => $row['Type'],
                ];
            }
        }

        $structuredData = [
            'appointments' => $appointments,

            // array_values() enlève les clés (les IDs) du tableau associatif
            // pour générer un vrai tableau JSON avec des crochets [ {..}, {..} ]
            'ressources'   => array_values($ressources)
        ];

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

    public function findEventsByEmployee(int $employeeId, string $type): array
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

    public function createEvent(array $data, LoggerInterface $logger): array
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

    public function divideEvent(int $id, array $data): array
    {
        try {
            $debutObj = new \DateTime()->setTimestamp((int)($data['DateCoupure'] / 1000));

            $conn = $this->getEntityManager()->getConnection();
            $sql = 'EXEC ps_PlanningEvenementDivide @IdEvenement = :IdEvenement, @DateCoupure = :DateCoupure';
            $params = [
                'IdEvenement' => $id,
                'DateCoupure' => $debutObj->format('Y-m-d\TH:i:s'),
            ];
            $result = $conn->executeQuery($sql, $params)->fetchAllAssociative();

            return $result[0];
        } catch (Exception $e) {
            throw new \Exception('Erreur lors de l\'exécution de la procédure stockée: ' . $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function repeatEvent(array $data): array
    {
        $conn = $this->getEntityManager()->getConnection();

        try {
            $createdIds = [];
            $idEmploye = $data['IdEmploye'];
            $idRessource = $data['IdPlanningRessource'];
            $annotation = $data['AnnotationPlanningEvenement'] ?? null;
            $type = $data['Type'];

            $conn->beginTransaction();
            foreach ($data['Date'] as $periode) {

                $debut = (new \DateTime())->setTimestamp((int)($periode['DebutPlanningEvenement'] / 1000))->format('Y-m-d\TH:i:s');
                $fin = (new \DateTime())->setTimestamp((int)($periode['FinPlanningEvenement'] / 1000))->format('Y-m-d\TH:i:s');


                $sql = 'EXEC ps_PlanningEvenementInsert @IdEmploye = :IdEmployee, @Type = :Type, @DebutPlanningEvenement = :DebutPlanningEvenement, @FinPlanningEvenement = :FinPlanningEvenement, @AnnotationPlanningEvenement = :AnnotationPlanningEvenement, @IdPlanningRessource = :IdPlanningRessource';
                $params = [
                    'IdEmployee' => $idEmploye,
                    'Type' => $type,
                    'DebutPlanningEvenement' => $debut,
                    'FinPlanningEvenement' => $fin,
                    'AnnotationPlanningEvenement' => $annotation,
                    'IdPlanningRessource' => $idRessource
                ];
                $id = $conn->executeQuery($sql, $params)->fetchAllAssociative()[0]['IdPlanningEvenement'];
                $createdIds[] = $id;
            }
            $conn->commit();
            return $createdIds;
        }catch (\Exception $e) {
            $conn->rollBack();
            throw new \Exception('Erreur lors de l\'exécution de la procédure stockée: ' . $e->getMessage());
        }
    }
}
