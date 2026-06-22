<?php

namespace App\Repository;

use App\Entity\Planningevenement;
use App\Entity\Session;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * @extends ServiceEntityRepository<Planningevenement>
 */
class PlanningEvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private CacheInterface $cache, private Security $security,)
    {
        parent::__construct($registry, Planningevenement::class);
    }

    private function structuredData(array $data): array
    {

        if (isset($data['IdPlanningEvenement'])) {
            $data = [$data];
        }

        $appointments = [];
        $ressources = [];

        $currentUser = $this->security->getUser();
        $currentUserId = $currentUser ? $currentUser->getUserIdentifier() : null;

        foreach($data as $row){
            $idRdv = (int)$row['IdPlanningEvenement'];
            $isLocked = false;
            $cacheKey = 'edit_rdv_' . $idRdv;

            $cacheItem = $this->cache->getItem($cacheKey);

            if ($cacheItem->isHit()) {
                // Le RDV est dans le cache Redis !
                $ownerId = $cacheItem->get();

                // Est-ce que le propriétaire du verrou est différent de moi ?
                // (Si ownerId === currentUserId, c'est mon verrou, donc ce n'est pas locked pour moi)
                if ($ownerId !== $currentUserId) {
                    $isLocked = true;
                }
            }

            $appointments[]= [
                'IdPlanningEvenement' => $idRdv,
                'isLocked' => $isLocked,
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
                    'IdImage'                         => $row['IdImage'] ? (int)$row['IdImage'] : null,
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

    public function findEventById(int $id, LoggerInterface $logger): array
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

            $logger->debug('Event found: ' . json_encode($result));

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
            $sql = 'EXEC ps_PlanningEvenementInsert
                    @IdEmploye = :IdEmploye,
                    @DebutPlanningEvenement = :DebutPlanningEvenement,
                    @FinPlanningEvenement = :FinPlanningEvenement,
                    @AnnotationPlanningEvenement = :AnnotationPlanningEvenement,
                    @IdPlanningRessource = :IdPlanningRessource,
                    @IdPlanningEtiquette = :IdPlanningEtiquette,
                    @PlanningEvenementPriorite = :PlanningEvenementPriorite
            ';
            $params = [
                'IdEmploye' => $data['IdEmploye'],
                'DebutPlanningEvenement' => $debutObj->format('Y-m-d\TH:i:s'),
                'FinPlanningEvenement' => $finObj->format('Y-m-d\TH:i:s'),
                'AnnotationPlanningEvenement' => $data['AnnotationPlanningEvenement'] ?? null,
                'IdPlanningRessource' => $data['IdPlanningRessource'],
                'IdPlanningEtiquette' => $data['IdPlanningEtiquette'] ?? null,
                'PlanningEvenementPriorite' => $data['PlanningEvenementPriorite'] ?? null,
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
            $sql = 'EXEC ps_PlanningEvenementUpdate @IdEvenement = :IdEvenement, @IdEmploye = :IdEmploye, @DebutPlanningEvenement = :DebutPlanningEvenement, @FinPlanningEvenement = :FinPlanningEvenement, @AnnotationPlanningEvenement = :AnnotationPlanningEvenement, @IdPlanningRessource = :IdPlanningRessource, @IdPlanningEtiquette = :IdPlanningEtiquette, @Priorite = :Priorite';
            $params = [
                'IdEvenement' => $id,
                'IdEmploye' => $data['IdEmploye'] ?? null,
                'DebutPlanningEvenement' => $debutObj->format('Y-m-d\TH:i:s'),
                'FinPlanningEvenement' => $finObj->format('Y-m-d\TH:i:s'),
                'AnnotationPlanningEvenement' => $data['AnnotationPlanningEvenement'] ?? null,
                'IdPlanningRessource' => $data['IdPlanningRessource'] ?? ($data['Ressource']['IdPlanningRessource'] ?? null) ?? null,
                'IdPlanningEtiquette' => $data['IdPlanningEtiquette'] ?? null,
                'Priorite' => $data['PlanningEvenementPriorite']
            ];
            $result = $conn->executeQuery($sql, $params)->fetchAllAssociative();

            $structuredData = [
                'IdPlanningEvenement' => (int)$result[0]['IdPlanningEvenement'],
                'DebutPlanningEvenement' => (int)$result[0]['DebutPlanningEvenement'],
                'FinPlanningEvenement' => (int)$result[0]['FinPlanningEvenement'],
                'AnnotationPlanningEvenement' => $result[0]['AnnotationPlanningEvenement'],
                'Etiquette' => [
                    'IdPlanningEtiquette' => $result[0]['IdPlanningEtiquette'] ?? null,
                    'LibelleLongPlanningEtiquette' => $result[0]['LibelleLongPlanningEtiquette'],
                    'LibelleCourtPlanningEtiquette' => $result[0]['LibelleCourtPlanningEtiquette']
                ],
                'IdPlanningRessource' => (int)$result[0]['IdPlanningRessource'],
                'IdEmploye' => (int)$result[0]['IdEmployee'],
                'PlanningEvenementPriorite' => (int)$result[0]['PlanningEvenementPriorite']
            ];





            return ['LignesModifiees' => $result[0]['LignesModifiees'], 'data' => $structuredData];

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

    public function divideEvent(int $id, array $data, LoggerInterface $logger): array
    {
        try {
            $debutObj = new \DateTime()->setTimestamp((int)($data['DateCoupure'] / 1000));

            $logger->debug('Dividing event with ID: ' . $id . ' at date: ' . $debutObj->format('Y-m-d\TH:i:s'));
            $conn = $this->getEntityManager()->getConnection();
            $sql = 'EXEC ps_PlanningEvenementDivide @IdEvenement = :IdEvenement, @DateCoupure = :DateCoupure';
            $params = [
                'IdEvenement' => $id,
                'DateCoupure' => $debutObj->format('Y-m-d\TH:i:s'),
            ];
            $result = $conn->executeQuery($sql, $params)->fetchAllAssociative();

            $structuredData = [
                'IdPlanningEvenement' => (int)$result[0]['IdPlanningEvenement'],
                'DebutPlanningEvenement' => (int)$result[0]['DebutPlanningEvenement'],
                'FinPlanningEvenement' => (int)$result[0]['FinPlanningEvenement'],
                'AnnotationPlanningEvenement' => $result[0]['AnnotationPlanningEvenement'],
                'Etiquette' => [
                    'IdPlanningEtiquette' => $result[0]['IdPlanningEtiquette'] ?? null,
                    'LibelleLongPlanningEtiquette' => $result[0]['LibelleLongPlanningEtiquette'],
                    'LibelleCourtPlanningEtiquette' => $result[0]['LibelleCourtPlanningEtiquette']
                ],
                'IdPlanningRessource' => (int)$result[0]['IdPlanningRessource'],
                'IdEmploye' => (int)$result[0]['IdEmployee'],
                'PlanningEvenementPriorite' => (int)$result[0]['PlanningEvenementPriorite']
            ];

            return $structuredData;
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
            $results = [];
            $idEmploye = $data['IdEmploye'];
            $idRessource = $data['IdPlanningRessource'];
            $annotation = $data['AnnotationPlanningEvenement'] ?? null;

            $conn->beginTransaction();
            foreach ($data['Date'] as $periode) {

                $debut = (new \DateTime())->setTimestamp((int)($periode['DebutPlanningEvenement'] / 1000))->format('Y-m-d\TH:i:s');
                $fin = (new \DateTime())->setTimestamp((int)($periode['FinPlanningEvenement'] / 1000))->format('Y-m-d\TH:i:s');


                $sql = 'EXEC ps_PlanningEvenementInsert @IdEmploye = :IdEmployee, @DebutPlanningEvenement = :DebutPlanningEvenement, @FinPlanningEvenement = :FinPlanningEvenement, @AnnotationPlanningEvenement = :AnnotationPlanningEvenement, @IdPlanningRessource = :IdPlanningRessource';
                $params = [
                    'IdEmployee' => $idEmploye,
                    'DebutPlanningEvenement' => $debut,
                    'FinPlanningEvenement' => $fin,
                    'AnnotationPlanningEvenement' => $annotation,
                    'IdPlanningRessource' => $idRessource
                ];
                $result = $conn->executeQuery($sql, $params)->fetchAllAssociative()[0];
                $id = $result['IdPlanningEvenement'];
                $createdIds[] = $id;
                $results[] = $result;
            }
            $conn->commit();
            return ['ids' => $createdIds, 'data' => $this->structuredData($results)];
        }catch (\Exception $e) {
            $conn->rollBack();
            throw new \Exception('Erreur lors de l\'exécution de la procédure stockée: ' . $e->getMessage());
        }
    }

    public function deleteEvents(array $data)
    {
        try {
            $conn = $this->getEntityManager()->getConnection();
            $conn->beginTransaction();

            foreach ($data['Ids'] as $id) {
                $sql = 'EXEC ps_PlanningEvenementDelete @IdEvenement = :IdEvenement';
                $params = [
                    'IdEvenement' => $id,
                ];
                $conn->executeStatement($sql, $params);
            }

            $conn->commit();
            return 1;
        } catch (\Throwable $e) {

            if (isset($conn) && $conn->isTransactionActive()) {
                $conn->rollBack();
            }

            // 5. On relance l'erreur pour que le contrôleur puisse renvoyer une erreur 500 au front
            throw new \Exception('Erreur lors de la suppression en masse : ' . $e->getMessage(), 0, $e);
        }
    }
}
