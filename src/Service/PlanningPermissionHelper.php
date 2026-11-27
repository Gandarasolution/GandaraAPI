<?php

namespace App\Service;

use App\Entity\Session;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Psr\Log\LoggerInterface;

class PlanningPermissionHelper
{
    public const DROIT_PROJET = 232;
    public const DROIT_PAIE = 231;
    public const DROIT_RUBRIQUE_PERSO = 233;

    public const NIVEAU_LECTURE = 2;
    public const NIVEAU_ECRITURE = 3;

    public function __construct(
        private readonly Connection $connection,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Récupère les détails du droit et vérifie si le niveau de l'utilisateur atteint le niveau requis.
     */
    public function getPermissionDetails(Session $user, int $idDroit, ?int $requiredLevel = null): array
    {
        if ($user->getIdpersonnel() === -1) {
            return [
                'level'    => PHP_INT_MAX,
                'hasRight' => true,
            ];
        }
        $this->logger->debug("Fetching permission details for User ID: {$user->getIdpersonnel()}, Right ID: $idDroit, Required Level: " . ($requiredLevel ?? 'None'));
        $sql = 'EXEC ps_PlanningDroitSelect @IdPersonnel = :id, @IdDroits = :idDroit';

        $result = $this->connection->fetchAssociative($sql, [
            'id'      => $user->getIdpersonnel(),
            'idDroit' => (string) $idDroit,
        ]);

        $this->logger->debug("Permission Details - User: {$user->getIdpersonnel()}, Right: $idDroit, Result: " . json_encode($result));

        if (!$result) {
            return [
                'level'    => 0,
                'hasRight' => false,
            ];
        }

        $niveau   = (int) $result['IdDroitNiveau'];
        $hasRight = $requiredLevel === null ? true : ($niveau >= $requiredLevel);

        return [
            'level'    => $niveau,
            'hasRight' => $hasRight,
        ];
    }

    /**
     * Récupère le niveau de l'utilisateur sur le droit standard (214).
     */
    public function getUserLevel(Session $user): int
    {
        $details = $this->getPermissionDetails($user, 214);

        return $details['hasRight'] ? $details['level'] : 21;
    }

    /**
     * Retourne le type de la ressource ('Projet', 'Paie', 'Rubrique Perso', ou 'Aucune').
     */
    public function getResourceType(int $idRessource): string
    {
        $sql = "SELECT
                    CASE
                        WHEN P.IdPlanningRessource IS NOT NULL THEN 'Projet'
                        WHEN S.IdPlanningRessource IS NOT NULL THEN 'Paie'
                        WHEN PRP.IdPlanningRessource IS NOT NULL THEN 'Rubrique Perso'
                        ELSE 'Aucune'
                    END AS TypeRessource
                FROM PlanningRessource PR
                LEFT JOIN Projet P ON P.IdPlanningRessource = PR.IdPlanningRessource
                LEFT JOIN SocialRubriquePaie S ON S.IdPlanningRessource = PR.IdPlanningRessource
                LEFT JOIN PlanningRubriquePersonnalise PRP ON PRP.IdPlanningRessource = PR.IdPlanningRessource
                WHERE PR.IdPlanningRessource = :idRessource";

        try {
            return (string) $this->connection->fetchOne($sql, ['idRessource' => $idRessource]);
        } catch (Exception $e) {
            $this->logger->error('Erreur SQL getResourceType : ' . $e->getMessage());
            return 'Aucune';
        }
    }

    /**
     * Vérifie si l'utilisateur dispose du niveau requis pour le type de ressource donné.
     */
    public function canAccessResource(Session $user, string $resourceType, int $requiredLevel): bool
    {
        $idDroit = match ($resourceType) {
            'Projet'         => self::DROIT_PROJET,
            'Paie'           => self::DROIT_PAIE,
            'Rubrique Perso' => self::DROIT_RUBRIQUE_PERSO,
            default          => null,
        };

        if ($idDroit === null) {
            return false;
        }

        return $this->getPermissionDetails($user, $idDroit, $requiredLevel)['hasRight'];
    }
}
