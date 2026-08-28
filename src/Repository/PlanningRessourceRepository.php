<?php

namespace App\Repository;

use App\Entity\Planningevenement;
use App\Entity\Planningressource;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


class PlanningRessourceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private UrlGeneratorInterface $router)
    {
        parent::__construct($registry, Planningressource::class);
    }


    public function getRessource($id){
        try {
            $conn = $this->getEntityManager()->getConnection();
            $sql = 'EXEC ps_PlanningRessourceSelect @ID = :id';
            $params = ['id' => $id];
            $data = $conn->executeQuery($sql, $params)->fetchAllAssociative();

            $structuredData = [];
            foreach($data as $row) {
                $structuredData[] = [
                    'IdPlanningRessource' => $row['IdPlanningRessource'],
                    'LibellePlanningRessource' => $row['LibellePlanningRessource'],
                    'Type' => $row['Type'],
                    'IdImage' => $row['IdImage'],
                    'Actif' => (int)$row['Actif'] === 1,
                    'CouleurFondPlanningRessource' => $row['CouleurFondPlanningRessource'],
                    'CouleurBordurePlanningRessource' => $row['CouleurBordurePlanningRessource'],
                    'CouleurTextePlanningRessource' => $row['CouleurTextePlanningRessource'],
                    'CodePlanningRessource' => $row['CodePlanningRessource']
                ];
            }

            return $structuredData;
        }catch (Exception $e) {
            throw new \Exception('Erreur lors de l\'exécution de la procédure stockée: ' . $e->getMessage());
        }
    }
    public function getRessources(mixed $query, mixed $limit, mixed $types, int $droitLevel)
    {
        try {
            $conn = $this->getEntityManager()->getConnection();
            $sql = 'EXEC ps_PlanningRessourceSelectSearch @Query = :query, @Limit = :limit, @Types = :types, @DroitLevel = :droitLevel';
            $params = [
                'query' => $query,
                'limit' => $limit,
                'types' => $types,
                'droitLevel' => $droitLevel
            ];

            $data = $conn->executeQuery($sql, $params)->fetchAllAssociative();

            $structuredData = [];
            foreach($data as $row) {
                $structuredData[] = [
                    'IdPlanningRessource' => $row['IdPlanningRessource'],
                    'LibellePlanningRessource' => $row['LibellePlanningRessource'],
                    'Type' => $row['Type'],
                    'IdImage' => $row['IdImage'],
                    'Actif' => (int)$row['Actif'] === 1
                ];
            }

            return $structuredData;

        }catch (Exception $e) {
            throw new \Exception('Erreur lors de l\'exécution de la procédure stockée: ' . $e->getMessage());
        }
    }


    public function updateRessource(int $id, mixed $data, LoggerInterface $logger)
    {
        try {
            $logger->debug('Données reçues pour la mise à jour de la ressource', ['id' => $id, 'data' => $data]);
            $conn = $this->getEntityManager()->getConnection();
            $sql = '
                EXEC ps_PlanningRessourceUpdate
                    @IdRessource = :Id,
                    @CouleurFondPlanningRessource = :CouleurFondPlanningRessource,
                    @CouleurBordurePlanningRessource = :CouleurBordurePlanningRessource,
                    @CouleurTextePlanningRessource = :CouleurTextePlanningRessource,
                    @IdImage = :IdImage,
                    @CodePlanningRessource = :CodePlanningRessource,
                    @Actif = :Actif,
                    @LibellePlanningRessource = :LibellePlanningRessource
            ';
            $params = [
                'Id' => $id,
                'CouleurFondPlanningRessource' => $data['CouleurFondPlanningRessource'] ?? null,
                'CouleurBordurePlanningRessource' => $data['CouleurBordurePlanningRessource'] ?? null,
                'CouleurTextePlanningRessource' => $data['CouleurTextePlanningRessource'] ?? null,
                'IdImage' => $data['IdImage'] ?? null,
                'CodePlanningRessource' => $data['CodePlanningRessource'] ?? null,
                'Actif' => (int)($data['Actif'] ?? 1),
                'LibellePlanningRessource' => $data['LibellePlanningRessource'] ?? null,
            ];

            $result = $conn->executeQuery($sql, $params)->fetchAllAssociative();

            $logger->debug('Résultat de la mise à jour de la ressource', ['result' => $result]);

            $row = $result[0];

            $lignesModifiees = $row['LignesModifiees'];

            unset($row['LignesModifiees']);

            return [
                'LignesModifiees' => $lignesModifiees,
                'data'            => $row
            ];
        }catch (Exception $e) {
            throw new \Exception('Erreur lors de l\'exécution de la procédure stockée: ' . $e->getMessage());
        }
    }


    public function getProjet(
        mixed $limit,
        mixed $pageNumber,
        mixed $query,
        string $chargeeAffaires,
        string $chefChantiers,
        string $codes,
        string $etats,
        LoggerInterface $logger
    ){
        try {
            $conn = $this->getEntityManager()->getConnection();
            $sql = 'EXEC ps_PlanningRessourceSelectProjet @Limit= :Limit, @PageNumber= :PageNumber, @Query= :Query, @ChargeeAffaires= :ChargeeAffaires, @ChefChantiers= :ChefChantiers, @Codes= :Codes, @Etats= :Etats';
            $params = [
                'Limit' => $limit ?? 20,
                'PageNumber' => $pageNumber ?? 1,
                'Query' => $query,
                'ChargeeAffaires' => $chargeeAffaires,
                'ChefChantiers' => $chefChantiers,
                'Codes' => $codes,
                'Etats' => $etats
            ];
            $result = $conn->executeQuery($sql,$params)->fetchAllAssociative();

            $structuredData = [];
            foreach($result as $row) {
                $image = null;
                if (!empty($row['IdPlanningImage'])) {
                    $image = [
                        'image' => $this->router->generate('api_serve_image_file', [
                            'id' => $row['IdPlanningImage']
                        ], UrlGeneratorInterface::ABSOLUTE_URL),
                        'id' => $row['IdPlanningImage']];
                }
                $structuredData[] = [
                    'IdPlanningRessource' => $row['IdPlanningRessource'],
                    'LibellePlanningRessource' => $row['LibellePlanningRessource'],
                    'Image' => $image,
                    'Actif' => (int)$row['Actif'] === 1,
                    'CouleurFondPlanningRessource' => $row['CouleurFondPlanningRessource'],
                    'CouleurBordurePlanningRessource' => $row['CouleurBordurePlanningRessource'],
                    'CouleurTextePlanningRessource' => $row['CouleurTextePlanningRessource'],
                    'Type' => 'Projet',
                    'CodePlanningRessource' => $row['Code'],
                    'PoleActivite' => $row['DesignationPoleActivite'],
                    'ChargeAffaire' => $row['ChargeAffaire'],
                    'ChefChantier' => $row['ChefChantier'],
                    'Etat' => $row['Etat'],
                    'Identifiant' => $row['Identifiant'],
                    'DateOS' => $row['DateOS'] ? (new \DateTime($row['DateOS']))->format('d/m/Y') : null,
                    'DateFin' => $row['DateFin'] ? (new \DateTime($row['DateFin']))->format('d/m/Y') : null,
                    'TM' => $row['TM'],
                    'HR' => $row['HR'],
                    'SH' => $row['SH'],
                    'DPF' => $row['DPF'],
                    'RPF' => $row['RPF'],
                    'AP' => $row['AP'],
                    'SP' => $row['SP'],
                ];
            }

            $ligneTotal = $result[0]['TotalLignes'] ?? 0;

            return
            [
                'data' => $structuredData,
                'TotalLignes' => $ligneTotal
            ];

        }catch (Exception $e) {
            throw new \Exception('Erreur lors de l\'exécution de la procédure stockée: ' . $e->getMessage());
        } catch (\DateMalformedStringException $e) {
            throw new \Exception('Erreur lors du formatage de la date: ' . $e->getMessage());
        }
    }

    public function getRubriquePaie(int $limit, int $pageNumber, string $query, string $codes, LoggerInterface $logger)
    {
        try {

            $conn = $this->getEntityManager()->getConnection();
            $sql = 'EXEC ps_PlanningRessourceSelectRubriquePaie @Limit= :Limit, @PageNumber= :PageNumber, @Query= :Query, @Codes= :Codes';
            $params = [
                'Limit' => $limit ?? 20,
                'PageNumber' => $pageNumber ?? 1,
                'Query' => $query,
                'Codes' => $codes,
            ];
            $result = $conn->executeQuery($sql,$params)->fetchAllAssociative();

            $structuredData = [];
            foreach($result as $row) {
                $image = null;
                if (!empty($row['IdPlanningImage'])) {
                    $image = [
                        'image' => $this->router->generate('api_serve_image_file', [
                            'id' => $row['IdPlanningImage']
                        ], UrlGeneratorInterface::ABSOLUTE_URL),
                        'id' => $row['IdPlanningImage']];
                }

                $structuredData[] = [

                    'IdPlanningRessource' => $row['IdSocialRubriquePaie'],
                    'LibellePlanningRessource' => $row['LibellePlanningRessource'],
                    'CouleurFondPlanningRessource' => $row['CouleurFondPlanningRessource'],
                    'CouleurBordurePlanningRessource' => $row['CouleurBordurePlanningRessource'],
                    'CouleurTextePlanningRessource' => $row['CouleurTextePlanningRessource'],
                    'Image' => $image,
                    'Type' => 'Paie',
                    'Actif' => (int)$row['Actif'] === 1,
                    'CodePlanningRessource' => $row['CodePlanningRessource'],
                    'Category' => $row['Category'],
                    'Verrou' => (int)$row['Verrou'] === 1,
                ];
            }

            $ligneTotal = $result[0]['TotalLignes'] ?? 0;

            return
                [
                    'data' => $structuredData,
                    'TotalLignes' => $ligneTotal
                ];

        }catch (Exception $e) {
            throw new \Exception('Erreur lors de l\'exécution de la procédure stockée: ' . $e->getMessage());
        }
    }

    public function getRubriqueManuel(int $limit, int $pageNumber, string $query, string $codes, LoggerInterface $logger)
    {
        try {

            $conn = $this->getEntityManager()->getConnection();
            $sql = 'EXEC ps_PlanningRessourceSelectRubriqueManuel @Limit= :Limit, @PageNumber= :PageNumber, @Query= :Query, @Codes= :Codes';
            $params = [
                'Limit' => $limit ?? 20,
                'PageNumber' => $pageNumber ?? 1,
                'Query' => $query,
                'Codes' => $codes,
            ];
            $result = $conn->executeQuery($sql,$params)->fetchAllAssociative();

            $structuredData = [];
            foreach($result as $row) {
                $image = null;
                if (!empty($row['IdPlanningImage'])) {
                    $image = [
                        'image' => $this->router->generate('api_serve_image_file', [
                            'id' => $row['IdPlanningImage']
                        ], UrlGeneratorInterface::ABSOLUTE_URL),
                        'id' => $row['IdPlanningImage']];
                }


                $structuredData[] = [
                    'IdPlanningRessource' => $row['IdPlanningRessource'],
                    'LibellePlanningRessource' => $row['LibellePlanningRessource'],
                    'CouleurFondPlanningRessource' => $row['CouleurFondPlanningRessource'],
                    'CouleurBordurePlanningRessource' => $row['CouleurBordurePlanningRessource'],
                    'CouleurTextePlanningRessource' => $row['CouleurTextePlanningRessource'],
                    'Image' => $image,
                    'Actif' => (int)$row['Actif'] === 1,
                    'CodePlanningRessource' => $row['CodePlanningRessource'],
                    'Verrou' => (int)$row['Verrou'] === 1,
                    'Type' => 'Rubrique Perso'
                ];
            }

            $ligneTotal = $result[0]['TotalLignes'] ?? 0;

            return
                [
                    'data' => $structuredData,
                    'TotalLignes' => $ligneTotal
                ];

        }catch (Exception $e) {
            throw new \Exception('Erreur lors de l\'exécution de la procédure stockée: ' . $e->getMessage());
        }
    }

    public function createRessource(mixed $data)
    {
        try {
            $conn = $this->getEntityManager()->getConnection();
            $sql = 'EXEC ps_PlanningRessourceManuelInsert
                    @LibellePlanningRessource = :LibellePlanningRessource,
                    @Actif = :Actif,
                    @Code = :Code,
                    @IdImage = :IdImage,
                    @CouleurFondPlanningRessource = :CouleurFondPlanningRessource,
                    @CouleurBordurePlanningRessource = :CouleurBordurePlanningRessource,
                    @CouleurTextePlanningRessource = :CouleurTextePlanningRessource';
            $params = [
                'LibellePlanningRessource' => $data['LibellePlanningRessource'],
                'Actif' => $data['Actif'],
                'Code' => $data['CodePlanningRessource'],
                'IdImage' => $data['IdImage'] ?? null,
                'CouleurFondPlanningRessource' => $data['CouleurFondPlanningRessource'],
                'CouleurBordurePlanningRessource' => $data['CouleurBordurePlanningRessource'],
                'CouleurTextePlanningRessource' => $data['CouleurTextePlanningRessource']
            ];

            return $conn->executeQuery($sql, $params)->fetchAllAssociative()[0]['IdPlanningRessource'];
        }catch (Exception $e) {
            throw new \Exception('Erreur lors de l\'exécution de la procédure stockée: ' . $e->getMessage());
        }
    }

    public function verifyCode(string $code)
    {
        try {
            $conn = $this->getEntityManager()->getConnection();
            $sql = '
                SELECT COUNT(Code) AS Count
                FROM (
                    SELECT CAST(IdProjet AS VARCHAR(20)) as Code
                    FROM Projet

                    UNION ALL

                    SELECT CodePlanningRubriquePersonalise as Code
                    FROM PlanningRubriquePersonnalise

                    UNION ALL

                    SELECT CodeSocialRubriquePaie as Code
                    FROM SocialRubriquePaie
                ) AS TableGlobale
                WHERE Code = :Code';
            $params = [
                'Code' => $code
            ];

            return (int)$conn->executeQuery($sql, $params)->fetchAllAssociative()[0]['Count'] > 0;
        }catch (Exception $e) {
            throw new \Exception('Erreur lors de l\'exécution de la procédure stockée: ' . $e->getMessage());
        }
    }


}
