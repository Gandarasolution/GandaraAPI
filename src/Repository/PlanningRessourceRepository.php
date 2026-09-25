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


    /**
     * @throws Exception
     */
    public function getRessource($id): array
    {
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
    }

    /**
     * @throws Exception
     */
    public function getRessources(mixed $query, mixed $limit, mixed $types, int $droitLevel): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'EXEC ps_PlanningRessourceSelectSearch @Query = :query, @Limit = :limit, @Types = :types, @DroitLevel = :droitLevel';
        $params = [
            'query' => $query,
            'limit' => $limit,
            'types' => $types,
            'droitLevel' => $droitLevel
        ];

        $data = $conn->executeQuery($sql, $params)->fetchAllAssociative();

        $baseImageUrl = $this->router->generate('api_serve_image_file', ['id' => 999999], UrlGeneratorInterface::ABSOLUTE_URL);
        $baseImageUrl = str_replace('999999', '', $baseImageUrl);



        $structuredData = [];
        foreach($data as $row) {
            $image = null;

            if (!empty($row['IdPlanningImage'])) {
                $image = $baseImageUrl . $row['IdPlanningImage'];
            }
            $structuredData[] = [
                'IdPlanningRessource' => $row['IdPlanningRessource'],
                'LibellePlanningRessource' => $row['LibellePlanningRessource'],
                'Type' => $row['Type'],
                'Image' => $image,
                'Actif' => (int)$row['Actif'] === 1
            ];
        }

        return $structuredData;
    }


    /**
     * @throws Exception
     */
    public function updateRessource(int $id, mixed $data, LoggerInterface $logger): array
    {
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
            'IdImage' => $data['IdPlanningImage'] ?? null,
            'CodePlanningRessource' => $data['CodePlanningRessource'] ?? null,
            'Actif' => (int)($data['Actif'] ?? 1),
            'LibellePlanningRessource' => $data['LibellePlanningRessource'] ?? null,
        ];

        $result = $conn->executeQuery($sql, $params)->fetchAllAssociative();

        $logger->debug('Résultat de la mise à jour de la ressource', ['result' => $result]);

        if (empty($result)) {
            throw new \RuntimeException("La procédure stockée n'a retourné aucun résultat pour la ressource $id.");
        }

        $row = $result[0];
        $lignesModifiees = $row['LignesModifiees'];
        unset($row['LignesModifiees']);

        return [
            'LignesModifiees' => $lignesModifiees,
            'data'            => $row
        ];

    }


    /**
     * @throws \DateMalformedStringException
     * @throws Exception
     */
    public function getProjet(
        mixed $limit,
        mixed $pageNumber,
        mixed $query,
        string $chargeeAffaires,
        string $chefChantiers,
        string $codes,
        string $etats,
        LoggerInterface $logger
    ): array
    {
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


        $baseImageUrl = $this->router->generate('api_serve_image_file', ['id' => 999999], UrlGeneratorInterface::ABSOLUTE_URL);
        $baseImageUrl = str_replace('999999', '', $baseImageUrl);

        $structuredData = [];
        foreach($result as $row) {

            $image = null;
            if (!empty($row['IdPlanningImage'])) {
                $image = [
                    'image' => $baseImageUrl . $row['IdPlanningImage'],
                    'id' => $row['IdPlanningImage']
                ];
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
                'DateOS' => $row['DateOS'] ? new \DateTime($row['DateOS'])->format('d/m/Y') : null,
                'DateFin' => $row['DateFin'] ? new \DateTime($row['DateFin'])->format('d/m/Y') : null,
                'TM' => $row['TM'],
                'HR' => $row['HR'],
                'SH' => $row['SH'],
                'DPF' => $row['DPF'],
                'RPF' => $row['RPF'],
                'AP' => $row['AP'],
                'SP' => $row['SP'],
            ];
        }

        $ligneTotal = !empty($result) ? (int)$result[0]['TotalLignes'] : 0;

        return
            [
                'data' => $structuredData,
                'TotalLignes' => $ligneTotal
            ];
    }

    /**
     * @throws Exception
     */
    public function getRubriquePaie(int $limit, int $pageNumber, string $query, string $codes, LoggerInterface $logger): array
    {

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

        $baseImageUrl = $this->router->generate('api_serve_image_file', ['id' => 999999], UrlGeneratorInterface::ABSOLUTE_URL);
        $baseImageUrl = str_replace('999999', '', $baseImageUrl);

        foreach($result as $row) {
            $image = null;
            if (!empty($row['IdPlanningImage'])) {
                $image = [
                    'image' => $baseImageUrl . $row['IdPlanningImage'],
                    'id' => $row['IdPlanningImage']
                ];
            }

            $structuredData[] = [

                'IdPlanningRessource' => $row['IdPlanningRessource'],
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

        $ligneTotal = !empty($result) ? (int)$result[0]['TotalLignes'] : 0;

        return
            [
                'data' => $structuredData,
                'TotalLignes' => $ligneTotal
            ];
    }

    /**
     * @throws Exception
     */
    public function getRubriqueManuel(int $limit, int $pageNumber, string $query, string $codes, LoggerInterface $logger): array
    {
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

        $baseImageUrl = $this->router->generate('api_serve_image_file', ['id' => 999999], UrlGeneratorInterface::ABSOLUTE_URL);
        $baseImageUrl = str_replace('999999', '', $baseImageUrl);

        foreach($result as $row) {
            $image = null;
            if (!empty($row['IdPlanningImage'])) {
                $image = [
                    'image' => $baseImageUrl . $row['IdPlanningImage'],
                    'id' => $row['IdPlanningImage']
                ];
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

        $ligneTotal = !empty($result) ? (int)$result[0]['TotalLignes'] : 0;

        return
            [
                'data' => $structuredData,
                'TotalLignes' => $ligneTotal
            ];
    }

    /**
     * @throws Exception
     */
    public function createRessource(mixed $data)
    {
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
            'IdImage' => $data['IdPlanningImage'] ?? null,
            'CouleurFondPlanningRessource' => $data['CouleurFondPlanningRessource'],
            'CouleurBordurePlanningRessource' => $data['CouleurBordurePlanningRessource'],
            'CouleurTextePlanningRessource' => $data['CouleurTextePlanningRessource']
        ];

        $result = $conn->executeQuery($sql, $params)->fetchAllAssociative();

        if (empty($result)) {
            throw new \RuntimeException("La procédure stockée n'a retourné aucun résultat lors de la création de la ressource.");
        }

        return $result[0]['IdPlanningRessource'];
    }

    /**
     * @throws Exception
     */
    public function verifyCode(string $code): bool
    {
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

        $result = $conn->executeQuery($sql, $params)->fetchAllAssociative();

        if (empty($result)) {
            return false;
        }

        return (int)$result[0]['Count'] > 0;
    }


}
