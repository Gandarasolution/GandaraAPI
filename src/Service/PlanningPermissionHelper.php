<?php

namespace App\Service;

use App\Entity\Session;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Psr\Log\LoggerInterface;

class PlanningPermissionHelper
{
    public function __construct(
        private Connection $connection,
        private LoggerInterface $logger
    ) {}

    /**
     * Récupère le niveau de droit et si le droit est actif pour un IdDroit spécifique.
     */
    public function getPermissionDetails(Session $user, int $idDroit): array
    {
        $sql = 'EXEC ps_PlanningDroitSelect @IdPersonnel = :id, @IdDroits = :idDroit';
        try {
            $result = $this->connection->fetchAssociative($sql, [
                'id' => $user->getIdpersonnel(),
                'idDroit' => (string) $idDroit,
            ]);

            if (!$result) {
                return ['level' => 21, 'gestionParNiveau' => true, 'hasRight' => false];
            }

            $niveau = (int) $result['IdDroitNiveau'];

            $gestionParNiveau = (bool) $result['GestionParNiveau'];

            if (!$gestionParNiveau) {
                return ['hasRight' => true];
            }

            return [
                'level' => $niveau,
                'gestionParNiveau' => true,
                'hasRight' => true,
            ];
        } catch (Exception $e) {
            $this->logger->error(sprintf('Erreur SQL ps_PlanningDroitSelect pour IdDroit %d : %s', $idDroit, $e->getMessage()));
            return ['level' => 21, 'gestionParNiveau' => true, 'hasRight' => false];
        }
    }



    /**
     * Récupère le niveau de l'utilisateur.
     */
    public function getUserLevel(Session $user): int
    {
        $sql = 'EXEC ps_PlanningDroitSelect @IdPersonnel = :id, @IdDroits = 214';
        try {
            $result = $this->connection->fetchAssociative($sql, [
                'id' => $user->getIdpersonnel(),
            ]);

            if (!$result) {
                return 21; // Niveau par défaut si aucun droit trouvé
            }

            return (int) $result['IdDroitNiveau'];
        } catch (Exception $e) {
            $this->logger->error(sprintf('Erreur SQL ps_PlanningDroitSelect pour IdPersonnel %d : %s', $user->getIdpersonnel(), $e->getMessage()));
            return 21; // Niveau par défaut en cas d'erreur
        }
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
     * Vérifie si le niveau utilisateur est suffisant par rapport au type de ressource.
     */
    public function hasAccessByResourceType(string $resourceType, int $userLevel): bool
    {
        return match ($resourceType) {
            'Projet' => in_array($userLevel, [22, 23], true),
            'Paie', 'Rubrique Perso' => $userLevel === 23,
            default => false,
        };
    }
}
