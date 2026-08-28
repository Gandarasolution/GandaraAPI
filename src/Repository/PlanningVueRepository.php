<?php

namespace App\Repository;

use App\Entity\Planningvue;
use App\Entity\Session;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\Exception;
use Monolog\Logger;
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

    public function getVue(int $id, LoggerInterface $logger)
    {
        $sql = 'EXEC ps_PlanningVueFiltreListeSelect @IdPlanningVue = :IdPlanningVue';
        $conn = $this->getEntityManager()->getConnection();

        try {
            $result = $conn->executeQuery($sql, ['IdPlanningVue' => $id])->fetchAllAssociative();

            $logger->debug('Résultat de la requête pour l\'ID ' . $id . ': ' . json_encode($result));

            if (!$result) {
                throw new \Exception('Auncun type de filtre trouvé pour cette vue: ' . $id);
            }
            $structuredData = [];

            foreach ($result as $row) {
                $sql = 'EXEC ps_PlanningVueFiltreValeurSelect @IdPlanningVue = :IdPlanningVue, @IdFiltre = :IdFiltre, @EstFiltreGandara = :EstFiltreGandara';
                $logger->debug('Row ' . ' : ' . json_encode($row));
                $params = [
                    'IdPlanningVue' => $id,
                    'IdFiltre' => $row['IdTypeFiltre'],
                    'EstFiltreGandara' => $row['EstFiltreGandara'],
                ];

                $values = $conn->executeQuery($sql, $params)->fetchAllAssociative();

                $logger->debug('Valeurs pour le filtre ' . $row['NomFiltre'] . ': ' . json_encode($values));
                $logger->debug('Row ' . ' : ' . json_encode($row));
                $structuredData[] = [
                    'IdFiltre' => $row['IdTypeFiltre'],
                    'LibelleFiltre' => $row['NomFiltre'],
                    'EstFiltreGandara' => $row['EstFiltreGandara'] === 1,
                    'Valeurs' => $values,
                ];

            }

            if ($id === 0){
                return ['filtrePerso' => $structuredData, 'planningVue' => [
                    'IdPlanningVue' => 0,
                    'DescriptionPlanningVue' => '',
                    'LibellePlanningVue' => '',
                    'Group' =>[
                        'ChampsPremierGroupePlanningVue' => '',
                        'ChampsDeuxiemeGroupePlanningVue' => ''
                    ],
                    'chantierEvenement' => false,
                    'paieEvenement' => false,
                    'persoEvenement' => false,
                    'IdPlanningImage' => null,
                    'isLocked' => false,
                ]];
            }
            $sql = 'SELECT * FROM PlanningVue WHERE IdPlanningVue = :IdPlanningVue';
            $params = [
                'IdPlanningVue' => $id,
            ];
            $result = $conn->executeQuery($sql, $params)->fetchAssociative();

            $structurePlanningVue = [
                'IdPlanningVue' => $id,
                'DescriptionPlanningVue' => $result['DescriptionPlanningVue'],
                'LibellePlanningVue' => $result['LibellePlanningVue'],
                'Group' =>[
                    'ChampsPremierGroupePlanningVue' => $result['ChampsPremierGroupePlanningVue'],
                    'ChampsDeuxiemeGroupePlanningVue' => $result['ChampsDeuxiemeGroupePlanningVue']
                ],
                'chantierEvenement' => $result['FiltreChantierPlanningVue'] === 1,
                'paieEvenement' => $result['FiltreSocialPlanningVue'] === 1,
                'persoEvenement' => $result ['FiltreAutresPlanningVue'] === 1,
                'IdPlanningImage' => $result['IdPlanningImage'],
                'isLocked' => false,
            ];

            $sql = 'SELECT S.Id, S.Nom
                    FROM PlanningVueAttribution
                    LEFT JOIN v_Personnel S ON S.Id = PlanningVueAttribution.IdSession
                    WHERE IdPlanningVue = :IdPlanningVue
                    ';
            $params = [
                'IdPlanningVue' => $id,
            ];
            $utilisateursAutorises = $conn->executeQuery($sql, $params)->fetchAllAssociative();

            return ['filtrePerso' => $structuredData, 'planningVue' => $structurePlanningVue, 'utilisateursAutorises' => $utilisateursAutorises];

        } catch (Exception $e) {
            throw new \Exception('Erreur lors de la récupération de la vue pour l\'ID ' . $id . ': ' . $e->getMessage());
        }
    }

    public function setVue(int $id, array $planningVue, array $filtrePerso, array $utilisateursAutorises, LoggerInterface $logger)
    {
        $conn = $this->getEntityManager()->getConnection();

        $jsonUsers = json_encode($utilisateursAutorises);
        $logger->debug('Mise à jour de la vue avec les données: ' . json_encode($planningVue) . ', filtres: ' . json_encode($filtrePerso) . ', utilisateurs: ' . $jsonUsers);
        try {
            $conn->beginTransaction();

            // Mise à jour de la vue
            $sqlSetplanningVue = '
                EXEC ps_PlanningVueUpdateInsert
                @IdPlanningVue = :IdPlanningVue,
                @DescriptionPlanningVue = :DescriptionPlanningVue,
                @LibellePlanningVue = :LibellePlanningVue,
                @ChampsPremierGroupePlanningVue = :ChampsPremierGroupePlanningVue,
                @ChampsDeuxiemeGroupePlanningVue = :ChampsDeuxiemeGroupePlanningVue,
                @FiltreChantierPlanningVue = :FiltreChantierPlanningVue,
                @FiltreSocialPlanningVue = :FiltreSocialPlanningVue,
                @FiltreAutresPlanningVue = :FiltreAutresPlanningVue,
                @IdPlanningImage = :IdPlanningImage,
                @JsonUtilisateurs = :JsonUtilisateurs';

            $conn->executeQuery($sqlSetplanningVue, [
                'IdPlanningVue' => $id,
                'DescriptionPlanningVue' => $planningVue['DescriptionPlanningVue'],
                'LibellePlanningVue' => $planningVue['LibellePlanningVue'],
                'ChampsPremierGroupePlanningVue' => $planningVue['Group']['ChampsPremierGroupePlanningVue'],
                'ChampsDeuxiemeGroupePlanningVue' => $planningVue['Group']['ChampsDeuxiemeGroupePlanningVue'],
                'FiltreChantierPlanningVue' => $planningVue['chantierEvenement'] ? 1 : 0,
                'FiltreSocialPlanningVue' => $planningVue['paieEvenement'] ? 1 : 0,
                'FiltreAutresPlanningVue' => $planningVue['persoEvenement'] ? 1 : 0,
                'IdPlanningImage' => $planningVue['IdPlanningImage'] ?? null,
                'JsonUtilisateurs' => $jsonUsers,
            ]);


            // Mise à jour des filtres personnalisés
            foreach ($filtrePerso as $filtre) {
                $logger->debug('Mise à jour du filtre: ' . json_encode($filtre));
                $sqlSetFilterPerso = 'EXEC ps_PlanningVueFiltreInsertUpdateDelete @IdPlanningVue = :IdPlanningVue, @IdFiltre = :IdFiltre, @EstFiltreGandara = :EstFiltreGandara, @ValeurFiltre = :ValeurFiltre';
                $conn->executeQuery($sqlSetFilterPerso, [
                    'IdPlanningVue' => $id,
                    'IdFiltre' => $filtre['IdFiltre'],
                    'EstFiltreGandara' => $filtre['EstFiltreGandara'],
                    'ValeurFiltre' => $filtre['Valeurs'] ? implode(', ', $filtre['Valeurs']) : null
                ]);
            }

            $conn->commit();

            $sql = 'SELECT * FROM PlanningVue WHERE IdPlanningVue = :IdPlanningVue';
            $params = [
                'IdPlanningVue' => $id,
            ];
            $result = $conn->executeQuery($sql, $params)->fetchAssociative();

            $structurePlanningVue = [
                'IdPlanningVue' => $id,
                'DescriptionPlanningVue' => $result['DescriptionPlanningVue'],
                'LibellePlanningVue' => $result['LibellePlanningVue'],
                'Group' =>[
                    'ChampsPremierGroupePlanningVue' => $result['ChampsPremierGroupePlanningVue'],
                    'ChampsDeuxiemeGroupePlanningVue' => $result['ChampsDeuxiemeGroupePlanningVue']
                ],
                'IdPlanningImage' => $result['IdPlanningImage'],
                'isLocked' => false,
            ];

            return $structurePlanningVue;

        } catch (Exception $e) {
            $conn->rollBack();
            throw new \Exception('Erreur lors de la mise à jour de la vue: ' . $e->getMessage());
        }


    }

    public function createVue(array $planningVue, array $filtrePerso, array $utilisateursAutorises, string $idPlanning, int $idUser, LoggerInterface $logger)
    {
        $conn =  $this->getEntityManager()->getConnection();

        $jsonUsers = json_encode($utilisateursAutorises);
        try {
            $sql = 'EXEC ps_PlanningVueUpdateInsert @IdPlanning = :IdPlanning, @IdSession = :IdSession, @DescriptionPlanningVue = :DescriptionPlanningVue, @LibellePlanningVue = :LibellePlanningVue, @ChampsPremierGroupePlanningVue = :ChampsPremierGroupePlanningVue, @ChampsDeuxiemeGroupePlanningVue = :ChampsDeuxiemeGroupePlanningVue, @FiltreChantierPlanningVue = :FiltreChantierPlanningVue, @FiltreSocialPlanningVue = :FiltreSocialPlanningVue, @FiltreAutresPlanningVue = :FiltreAutresPlanningVue, @IdPlanningImage = :IdPlanningImage, @JsonUtilisateurs = :JsonUtilisateurs';

            $conn->beginTransaction();

            $params = [
                'IdPlanning' => $idPlanning,
                'IdSession' => $idUser,
                'DescriptionPlanningVue' => $planningVue['DescriptionPlanningVue'],
                'LibellePlanningVue' => $planningVue['LibellePlanningVue'],
                'ChampsPremierGroupePlanningVue' => $planningVue['Group']['ChampsPremierGroupePlanningVue'],
                'ChampsDeuxiemeGroupePlanningVue' => $planningVue['Group']['ChampsDeuxiemeGroupePlanningVue'],
                'FiltreChantierPlanningVue' => $planningVue['chantierEvenement'] ? 1 : 0,
                'FiltreSocialPlanningVue' => $planningVue['paieEvenement'] ? 1 : 0,
                'FiltreAutresPlanningVue' => $planningVue['persoEvenement'] ? 1 : 0,
                'IdPlanningImage' => $planningVue['IdPlanningImage'] ?? null,
                'JsonUtilisateurs' => $jsonUsers,
            ];

            $result = $conn->executeQuery($sql, $params)->fetchAssociative();
            $logger->debug('Résultat de la création de la vue: ' . json_encode($result));

            if ($result === false) {
                // Si tu arrives ici sans que le THROW n'ait déclenché d'Exception
                return['error' => 1, 'message' => 'Échec de la création en base de données.'];
            }

            $newId = $result['IdPlanningVue'];

            $result = [
                'IdPlanningVue' => (int)$newId,
                'DescriptionPlanningVue' => $result['DescriptionPlanningVue'],
                'LibellePlanningVue' => $result['LibellePlanningVue'],
                'Group' =>[
                    'ChampsPremierGroupePlanningVue' => $result['ChampsPremierGroupePlanningVue'],
                    'ChampsDeuxiemeGroupePlanningVue' => $result['ChampsDeuxiemeGroupePlanningVue']
                ],
                'IdPlanningImage' => $result['IdPlanningImage'],
                'isLocked' => false,
            ];

            foreach ($filtrePerso as $filtre) {
                $logger->debug('Mise à jour du filtre: ' . json_encode($filtre));
                $sqlSetFilterPerso = 'EXEC ps_PlanningVueFiltreInsertUpdateDelete @IdPlanningVue = :IdPlanningVue, @IdFiltre = :IdFiltre, @EstFiltreGandara = :EstFiltreGandara, @ValeurFiltre = :ValeurFiltre';
                $conn->executeQuery($sqlSetFilterPerso, [
                    'IdPlanningVue' => $newId,
                    'IdFiltre' => $filtre['IdFiltre'],
                    'EstFiltreGandara' => $filtre['EstFiltreGandara'],
                    'ValeurFiltre' => $filtre['Valeurs'] ? implode(', ', $filtre['Valeurs']) : null
                ]);
            }

            $conn->commit();
            return ['error' => 0, 'message' => 'Nouvelle vue créée avec succès.', 'data' => $result];
        } catch (Exception $e) {
            $conn->rollBack();
            throw new \Exception('Erreur lors de la création de la nouvelle vue: ' . $e->getMessage());
        }
    }

    public function deleteVue(int $id, LoggerInterface $logger)
    {
        $conn = $this->getEntityManager()->getConnection();

        try {
            $sql = 'EXEC ps_PlanningVueDelete @IdPlanningVue = :IdPlanningVue';
            $params = [
                'IdPlanningVue' => $id,
            ];

            $result = $conn->fetchAssociative($sql, $params);

            if (!$result) return ['error' => 1, 'message' => 'Erreur lors de la suppression de la vue.'];

            $nbLignes = $result['LignesAffectees'];

            if ($nbLignes === 0) {
                $logger->warning("Aucune vue n'a été supprimée (ID introuvable : $id)");
                return ['error' => 1, 'message' => 'Erreur lors de la suppression de la vue.'];
            } else {
                $logger->info("$nbLignes vue(s) supprimée(s) avec succès.");

                return ['error' => 0, 'message' => 'Vue supprimée avec succès.'];
            }

        }catch(Exception $e){
            throw new \Exception('Erreur lors de la création de la nouvelle vue: ' . $e->getMessage());
        }
    }

    public function getUsers()
    {
        $conn = $this->getEntityManager()->getConnection();

        try {
            $sql = 'SELECT IdPersonnel AS Id, V.Nom
                    FROM Session
                    INNER JOIN v_Personnel V ON V.Id = Session.IdPersonnel
                    WHERE IdPersonnel > 0;';
            $result = $conn->executeQuery($sql)->fetchAllAssociative();

            return $result;
        } catch (Exception $e) {
            throw new \Exception('Erreur lors de la récupération des utilisateurs: ' . $e->getMessage());
        }
    }
}
