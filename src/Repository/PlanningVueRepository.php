<?php

namespace App\Repository;

use App\Entity\Planningvue;
use App\Entity\Session;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Cache\CacheInterface;


class PlanningVueRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry, private CacheInterface $cache, private Security $security)
    {
        parent::__construct($registry, PlanningVue::class);
    }

    public function getConfigUser(int $idSession, int $idPlanning)
    {
        $currentUser = $this->security->getUser();
        $currentUserId = $currentUser?->getUserIdentifier();

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
                $idVue = (int)$row['IdPlanningVue'];
                $isLocked = false;
                $cacheKey = 'edit_config_' . $idVue;

                $cacheItem = $this->cache->getItem($cacheKey);

                if ($cacheItem->isHit()) {
                    $ownerId = $cacheItem->get();

                    // Est-ce que le propriétaire du verrou est différent de moi ?
                    // (Si ownerId === currentUserId, c'est mon verrou, donc ce n'est pas locked pour moi)
                    if ($ownerId !== $currentUserId) {
                        $isLocked = true;
                    }
                }

                $structuredData[] = [
                    'IdPlanningVue' => $idVue,
                    'DescriptionPlanningVue' => $row['DescriptionPlanningVue'],
                    'LibellePlanningVue' => $row['LibellePlanningVue'],
                    'Group' =>[
                        'ChampsPremierGroupePlanningVue' => $row['ChampsPremierGroupePlanningVue'],
                        'ChampsDeuxiemeGroupePlanningVue' => $row['ChampsDeuxiemeGroupePlanningVue']
                    ],
                    'IdPlanningImage' => $row['IdPlanningImage'],
                    'isLocked' => $isLocked,
                ];
            }



            return ['Configs' => $structuredData];
        } catch (Exception $e) {
            throw new \Exception('Erreur lors de la récupération des configurations pour l\'utilisateur ' . $idSession . ': ' . $e->getMessage());
        }
    }

    public function getNonWorkingDates(int $idPlanning){

        try {
            $conn = $this->getEntityManager()->getConnection();

            $sql = 'EXEC ps_PlanningJourNonTravailleSelect @IdPlanning = :IdPlanning';
            $params = [
                'IdPlanning' => $idPlanning,
            ];
            $result = $conn->executeQuery($sql, $params)->fetchAllAssociative();

            return $result;
        }catch (Exception $e){
            throw new \Exception('Erreur lors de la récupération des jours non travaillés pour le planning ' . $idPlanning . ': ' . $e->getMessage());
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


    public function getLastVue(Session $user, int $idPlanning)
    {
        $conn = $this->getEntityManager()->getConnection();
        $idPersonnel = $user->getIdpersonnel();

        try {
            // 1. Tente de récupérer la dernière vue de l'utilisateur
            $sqlPref = 'EXEC ps_ParametreSelect @IdParametre = \'Planning.DerniereVue\', @IdPersonnel = :IdPersonnel, @Mode = 2';
            $prefResult = $conn->executeQuery($sqlPref, ['IdPersonnel' => $idPersonnel])->fetchAssociative();

            // 2. Si une préférence est trouvée, on vérifie qu'elle existe toujours
            if ($prefResult && isset($prefResult['ValeurParametre'])) {
                $sqlCheck = 'SELECT IdPlanningVue FROM PlanningVue WHERE IdPlanningVue = :idPlanningVue';
                $existingVue = $conn->executeQuery($sqlCheck, ['idPlanningVue' => $prefResult['ValeurParametre']])->fetchAssociative();

                // Si elle existe, on retourne directement le résultat (Fin de la fonction)
                if ($existingVue) {
                    return $existingVue;
                }
            }

            // 3. FALLBACK : Si on arrive ici, c'est qu'il n'y a pas de préférence OU qu'elle a été supprimée.
            // On exécute la requête de secours (écrite une seule fois).
            $sqlFallback = 'EXEC ps_PlanningVueSelect @IdPlanning = :idPlanning, @IdSession = :idPersonnel';

            $vuesDisponibles = $conn->executeQuery($sqlFallback, [
                'idPlanning' => $idPlanning,
                'idPersonnel' => $idPersonnel,
            ])->fetchAllAssociative();

            // Si vraiment aucune vue n'est dispo, on lève l'exception
            if (!$vuesDisponibles) {
                throw new \Exception(sprintf("Aucune vue disponible pour l'utilisateur %d et le planning %d", $idPersonnel, $idPlanning));
            }

            return $vuesDisponibles;

        } catch (\Throwable $e) {
            // On attrape \Throwable (qui inclut les erreurs PDO et exceptions) pour être plus large
            throw new \Exception(sprintf("Erreur lors de la récupération de la vue pour l'utilisateur %d : %s", $idPersonnel, $e->getMessage()));
        }
    }

    public function setLastVue(Session $user, int $idVue)
    {
        $sql = 'EXEC ps_ParametreSessionInsertUpdate @IdParametre = \'Planning.DerniereVue\', @IdPersonnel = :IdPersonnel, @ValeurParametreSession = :ValeurParametreSession';
        $conn = $this->getEntityManager()->getConnection();

        try {
            $conn->executeQuery($sql, [
                'IdPersonnel' => $user->getIdpersonnel(),
                'ValeurParametreSession' => $idVue,
            ]);
        } catch (Exception $e) {
            throw new \Exception('Erreur lors de la mise à jour de la dernière vue pour l\'utilisateur ' . $user->getIdpersonnel() . ': ' . $e->getMessage());
        }
    }
}
