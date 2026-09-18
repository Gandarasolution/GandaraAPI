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

    /**
     * @throws Exception
     */
    public function getConfigUser(int $idSession, int $idPlanning): array
    {
        $currentUser = $this->security->getUser();
        $currentUserId = $currentUser?->getUserIdentifier();


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
                'Group' => [
                    'ChampsPremierGroupePlanningVue' => $row['ChampsPremierGroupePlanningVue'],
                    'ChampsDeuxiemeGroupePlanningVue' => $row['ChampsDeuxiemeGroupePlanningVue']
                ],
                'IdPlanningImage' => $row['IdPlanningImage'],
                'isLocked' => $isLocked,
            ];
        }


        return ['Configs' => $structuredData];
    }


    /**
     * @throws Exception
     */
    public function getNonWorkingDates(int $idPlanning): array
    {

        $conn = $this->getEntityManager()->getConnection();

        $sql = 'EXEC ps_PlanningJourNonTravailleSelect @IdPlanning = :IdPlanning';
        $params = [
            'IdPlanning' => $idPlanning,
        ];
        $result = $conn->executeQuery($sql, $params)->fetchAllAssociative();

        return $result;

    }

    /**
     * @throws Exception
     */
    public function createNonWorkingDates(array $data, mixed $IdPlanning, LoggerInterface $logger)
    {

        $date = new \DateTime()->setTimestamp((int)($data['nonWorkingDate'] / 1000));

        $conn = $this->getEntityManager()->getConnection();
        $sql = 'EXEC ps_PlanningJourNonTravailleInsert @IdPlanning = :IdPlanning, @DatePlanningJourNontravaille = :DatePlanningJourNontravaille';
        $dbResult = $conn->executeQuery($sql, [
            'IdPlanning' => $IdPlanning,
            'DatePlanningJourNontravaille' => $date->format('Y-m-d\TH:i:s'), // Plus sûr en SQL
        ])->fetchAllAssociative();

        if (empty($dbResult)) {
            throw new \RuntimeException("La procédure d'insertion du jour non travaillé n'a rien retourné.");
        }

        $result = $dbResult[0];

        if ((int)$result['LignesInserees'] === 0) {
            throw new \RuntimeException("Aucun jour non travaillé n'a été ajouté. Veuillez vérifier les données fournies.");
        }

        $logger->debug(json_encode($result));

        return $result['NouvelId'];
    }

    /**
     * @throws Exception
     */
    public function deleteNonWorkingDates(int $idDate): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'EXEC ps_PlanningJourNonTravailleDelete @IdDate = :IdDate';
        $dbResult = $conn->executeQuery($sql, ['IdDate' => $idDate])->fetchAllAssociative();

        if (empty($dbResult)) {
            throw new \RuntimeException("La procédure de suppression du jour non travaillé n'a rien retourné.");
        }

        $result = $dbResult[0];

        if (!isset($result['LignesSupprimee']) || (int)$result['LignesSupprimee'] === 0) {
            throw new \RuntimeException("Aucun jour non travaillé n'a été supprimé (ID introuvable).");
        }


        return ['LignesSupprimee' => $result, 'date' => $result['DatePlanningJourNontravaille']];
    }


    /**
     * @throws Exception
     */
    public function getLastVue(Session $user, int $idPlanning): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $idPersonnel = $user->getIdpersonnel();

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
            throw new \RuntimeException(printf("Aucune vue disponible pour l'utilisateur %d et le planning %d", $idPersonnel, $idPlanning));
        }

        return $vuesDisponibles;

    }

    /**
     * @throws Exception
     */
    public function setLastVue(Session $user, int $idVue): void
    {
        $sql = 'EXEC ps_ParametreSessionInsertUpdate @IdParametre = \'Planning.DerniereVue\', @IdPersonnel = :IdPersonnel, @ValeurParametreSession = :ValeurParametreSession';
        $conn = $this->getEntityManager()->getConnection();

        $conn->executeQuery($sql, [
            'IdPersonnel' => $user->getIdpersonnel(),
            'ValeurParametreSession' => $idVue,
        ]);
    }

    /**
     * @throws Exception
     */
    public function getVue(int $id, LoggerInterface $logger): array
    {
        $sql = 'EXEC ps_PlanningVueFiltreListeSelect @IdPlanningVue = :IdPlanningVue';
        $conn = $this->getEntityManager()->getConnection();

        $result = $conn->executeQuery($sql, ['IdPlanningVue' => $id])->fetchAllAssociative();

        $logger->debug('Résultat de la requête pour l\'ID ' . $id . ': ' . json_encode($result));

        if (!$result) {
            throw new \RuntimeException('Auncun type de filtre trouvé pour cette vue: ' . $id);
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

        if ($id === 0) {
            return ['filtrePerso' => $structuredData, 'planningVue' => [
                'IdPlanningVue' => 0,
                'DescriptionPlanningVue' => '',
                'LibellePlanningVue' => '',
                'Group' => [
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
            'Group' => [
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

    }

    /**
     * =     *@throws \Throwable
     */
    public function setVue(int $id, array $planningVue, array $filtrePerso, array $utilisateursAutorises, LoggerInterface $logger): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $jsonUsers = json_encode($utilisateursAutorises);
        $logger->debug('Mise à jour de la vue avec les données: ' . json_encode($planningVue) . ', filtres: ' . json_encode($filtrePerso) . ', utilisateurs: ' . $jsonUsers);

        return $conn->transactional(function ($conn) use ($id, $planningVue, $filtrePerso, $jsonUsers, $logger) {
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

            $sql = 'SELECT * FROM PlanningVue WHERE IdPlanningVue = :IdPlanningVue';
            $params = [
                'IdPlanningVue' => $id,
            ];
            $result = $conn->executeQuery($sql, $params)->fetchAssociative();
            if (!$result) {
                throw new \RuntimeException("Impossible de relire la vue après sa mise à jour.");
            }

            $structurePlanningVue = [
                'IdPlanningVue' => $id,
                'DescriptionPlanningVue' => $result['DescriptionPlanningVue'],
                'LibellePlanningVue' => $result['LibellePlanningVue'],
                'Group' => [
                    'ChampsPremierGroupePlanningVue' => $result['ChampsPremierGroupePlanningVue'],
                    'ChampsDeuxiemeGroupePlanningVue' => $result['ChampsDeuxiemeGroupePlanningVue']
                ],
                'IdPlanningImage' => $result['IdPlanningImage'],
                'isLocked' => false,
            ];

            return $structurePlanningVue;
        });

    }

    /**
     * @throws \Throwable
     */
    public function createVue(array $planningVue, array $filtrePerso, array $utilisateursAutorises, string $idPlanning, int $idUser, LoggerInterface $logger)
    {
        $conn =  $this->getEntityManager()->getConnection();

        $logger->debug('Mise à jour de la vue avec les données: ' . json_encode($planningVue) . ', filtres: ' . json_encode($filtrePerso) . ', utilisateurs: ' . json_encode($utilisateursAutorises));

        return $conn->transactional(function ($conn) use ($planningVue, $filtrePerso, $utilisateursAutorises, $idPlanning, $idUser, $logger) {
            $sql = 'EXEC ps_PlanningVueUpdateInsert @IdPlanning = :IdPlanning, @IdSession = :IdSession, @DescriptionPlanningVue = :DescriptionPlanningVue, @LibellePlanningVue = :LibellePlanningVue, @ChampsPremierGroupePlanningVue = :ChampsPremierGroupePlanningVue, @ChampsDeuxiemeGroupePlanningVue = :ChampsDeuxiemeGroupePlanningVue, @FiltreChantierPlanningVue = :FiltreChantierPlanningVue, @FiltreSocialPlanningVue = :FiltreSocialPlanningVue, @FiltreAutresPlanningVue = :FiltreAutresPlanningVue, @IdPlanningImage = :IdPlanningImage, @JsonUtilisateurs = :JsonUtilisateurs';

            $jsonUsers = json_encode($utilisateursAutorises);

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
                throw new \RuntimeException("La création de la vue a échoué (aucun retour de la procédure).");
            }

            $newId = $result['IdPlanningVue'];

            $result = [
                'IdPlanningVue' => (int)$newId,
                'DescriptionPlanningVue' => $result['DescriptionPlanningVue'],
                'LibellePlanningVue' => $result['LibellePlanningVue'],
                'Group' => [
                    'ChampsPremierGroupePlanningVue' => $result['ChampsPremierGroupePlanningVue'],
                    'ChampsDeuxiemeGroupePlanningVue' => $result['ChampsDeuxiemeGroupePlanningVue']
                ],
                'IdPlanningImage' => $result['IdPlanningImage'],
                'isLocked' => false,
            ];

            foreach ($filtrePerso as $filtre) {
                $valeursFormatees = array_map(function ($val) {
                    return "'" . $val . "'";
                }, $filtre['Valeurs']);
                $valeurFiltre = implode(', ', $valeursFormatees);

                $logger->debug('Mise à jour du filtre: ' . json_encode($filtre));
                $sqlSetFilterPerso = 'EXEC ps_PlanningVueFiltreInsertUpdateDelete @IdPlanningVue = :IdPlanningVue, @IdFiltre = :IdFiltre, @EstFiltreGandara = :EstFiltreGandara, @ValeurFiltre = :ValeurFiltre';
                $conn->executeQuery($sqlSetFilterPerso, [
                    'IdPlanningVue' => $newId,
                    'IdFiltre' => $filtre['IdFiltre'],
                    'EstFiltreGandara' => $filtre['EstFiltreGandara'],
                    'ValeurFiltre' => $valeurFiltre
                ]);
            }
            return $result;

        });


    }

    /**
     * @throws Exception
     */
    public function deleteVue(int $id, LoggerInterface $logger): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'EXEC ps_PlanningVueDelete @IdPlanningVue = :IdPlanningVue';
        $params = [
            'IdPlanningVue' => $id,
        ];

        $result = $conn->fetchAssociative($sql, $params);

        if (!$result) throw new \RuntimeException("Erreur lors de la suppression de la vue (ID : $id). Aucune réponse de la procédure stockée.");

        $nbLignes = $result['LignesAffectees'];

        if ($nbLignes === 0) {
            $logger->warning("Aucune vue n'a été supprimée (ID introuvable : $id)");
            return [];
        } else {
            $logger->info("$nbLignes vue(s) supprimée(s) avec succès.");

            return ['message' => 'Vue supprimée avec succès.'];
        }

    }

    /**
     * @throws Exception
     */
    public function getUsers()
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = 'SELECT IdPersonnel AS Id, V.Nom
                FROM Session
                INNER JOIN v_Personnel V ON V.Id = Session.IdPersonnel
                WHERE IdPersonnel > 0;';
        $result = $conn->executeQuery($sql)->fetchAllAssociative();

        return $result;

    }

    /**
     * @throws Exception
     */
    public function getAffichageMobile(int $idPersonnel, LoggerInterface $logger): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = 'EXEC ps_PlanningAffichageMobileConfigSelect @IdPersonnel = :IdPersonnel';
        $params = [
            'IdPersonnel' => $idPersonnel
        ];
        $result = $conn->executeQuery($sql, $params)->fetchAllAssociative();

        $logger->debug('Résultat de la requête pour l\'affichage mobile de l\'utilisateur ' . $idPersonnel . ': ' . json_encode($result));

        $formattedData = [
            'primaryFields' => [],
            'secondaryFields' => []
        ];

        foreach ($result as $row) {
            $logger->debug('result' . json_encode($result));
            if ($row['ZoneAffichage'] === 'primaire') {
                $formattedData['primaryFields'][] = $row['CodeChamp'];
            } elseif ($row['ZoneAffichage'] === 'secondaire') {
                $formattedData['secondaryFields'][] = $row['CodeChamp'];
            }
        }

        return $formattedData;
    }


    /**
     * @throws Exception
     */
    public function getAffichageSettingsMobile(int $idPersonnel, LoggerInterface $logger): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = 'EXEC ps_PlanningAffichageMobileConfigSelect @IdPersonnel = :IdPersonnel';
        $params = ['IdPersonnel' => $idPersonnel];
        $result = $conn->executeQuery($sql, $params)->fetchAllAssociative();

        $fieldsMap = [];
        $primaryConfigRaw = [];
        $secondaryConfigRaw = [];

        foreach ($result as $row) {
            $fieldValue = $row['CodeChamp'];

            // 1. Construction de la liste des champs disponibles (utilisation de la clé pour éviter les doublons)
            if (!isset($fieldsMap[$fieldValue])) {
                $fieldsMap[$fieldValue] = [
                    'CodeChamp' => $fieldValue,
                    'Libelle' => $row['Libelle'],
                    'Ordre' => (int)$row['Ordre']
                ];
            }

            // 2. Répartition dans les zones de configuration si le champ y est affecté
            if ($row['ZoneAffichage'] === 'primaire') {
                $primaryConfigRaw[] = ['CodeChamp' => $fieldValue, 'Position' => (int)$row['Position']];
            } elseif ($row['ZoneAffichage'] === 'secondaire') {
                $secondaryConfigRaw[] = ['CodeChamp' => $fieldValue, 'Position' => (int)$row['Position']];
            }
        }

        // 3. Tri des configurations selon l'ordre de positionnement défini en base
        usort($primaryConfigRaw, fn($a, $b) => $a['Position'] <=> $b['Position']);
        usort($secondaryConfigRaw, fn($a, $b) => $a['Position'] <=> $b['Position']);

        // 4. Assemblage final au format JSON attendu par le front-end
        $formattedOutput = [
            'fields' => array_values($fieldsMap),
            'config' => [
                'primaryFields' => array_column($primaryConfigRaw, 'CodeChamp'),
                'secondaryFields' => array_column($secondaryConfigRaw, 'CodeChamp')
            ]
        ];

        return $formattedOutput;

    }


    /**
     * @throws Exception
     */
    public function getAffichageSettingsMobileSave(int $idPersonnel, string $jsonPayload, LoggerInterface $logger): int
    {
        $logger->info('Début de la sauvegarde de la configuration mobile', [
            'idPersonnel' => $idPersonnel
        ]);

        $conn = $this->getEntityManager()->getConnection();

        $sql = 'EXEC ps_PlanningAffichageMobileConfigUpdateInsert @IdPersonnel = :IdPersonnel, @JsonPayload = :JsonPayload';
        $params = [
            'IdPersonnel' => $idPersonnel,
            'JsonPayload' => $jsonPayload
        ];

        $conn->executeStatement($sql, $params);

        $logger->info('Sauvegarde de la configuration mobile réussie', [
            'idPersonnel' => $idPersonnel
        ]);

        return 0;
    }
}
