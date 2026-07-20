<?php

namespace App\Security\Voter;

use App\Entity\Planningevenement;
use App\Entity\Session;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class EvenementVoter extends Voter
{

    public const CREATE = 'CREATE_EVENEMENT';
    public const UPDATE = 'UPDATE_EVENEMENT';
    public const DELETE = 'DELETE_EVENEMENT';
    public const REPEAT = 'REPEAT_EVENEMENT';
    public const MASS_DELETE = 'MASS_DELETE_EVENEMENT';

    public const VIEW_ALL = 'VIEW_ALL';
    public const LOCK = 'EVENEMENT_LOCK';


    public function __construct(private Connection $connection, private LoggerInterface $logger) {}


    protected function supports(string $attribute, mixed $subject): bool
    {
        if (in_array($attribute, [self::VIEW_ALL, self::LOCK])) return true;
        if (in_array($attribute,[self::CREATE, self::REPEAT])) return is_numeric($subject);

        // <-- GESTION DU TABLEAU POUR LA SUPPRESSION DE MASSE
        if ($attribute === self::MASS_DELETE) return is_array($subject);

        return in_array($attribute, [self::UPDATE, self::DELETE])
            && $subject instanceof Planningevenement;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof Session) {
            return false;
        }

        // 1. On récupère le niveau de l'utilisateur (on en a toujours besoin)
        $sql = 'EXEC ps_PlanningDroitSelect @IdPersonnel = :id';
        try {
            $planningDroit = $this->connection->fetchAssociative($sql, ['id' => $user->getIdpersonnel()]);
        } catch (Exception $e) {
            return false;
        }
        $level = (int)($planningDroit['IdDroitNiveau'] ?? 21);

        // 2. Vérification des actions GLOBALES (Pas besoin d'une ressource existante)
        if ($attribute === self::VIEW_ALL) {
            return $level === 23 || $level === 22;
        }

        // =========================================================
        // 2. CAS SPÉCIFIQUE : SUPPRESSION DE MASSE (Tableau d'IDs d'événements)
        // =========================================================
        if ($attribute === self::MASS_DELETE) {
            $eventIds = $subject;

            // Requête pour récupérer les types distincts de toutes les ressources touchées
            $sqlBulk = "SELECT DISTINCT
                            CASE
                                WHEN P.IdPlanningRessource IS NOT NULL THEN 'Projet'
                                WHEN S.IdPlanningRessource IS NOT NULL THEN 'Paie'
                                WHEN PRP.IdPlanningRessource IS NOT NULL THEN 'Rubrique Perso'
                                ELSE 'Aucune'
                            END AS TypeRessource
                        FROM PlanningEvenement PE
                        JOIN PlanningRessource PR ON PE.IdPlanningRessource = PR.IdPlanningRessource
                        LEFT JOIN Projet P ON P.IdPlanningRessource = PR.IdPlanningRessource
                        LEFT JOIN SocialRubriquePaie S ON S.IdPlanningRessource = PR.IdPlanningRessource
                        LEFT JOIN PlanningRubriquePersonnalise PRP ON PRP.IdPlanningRessource = PR.IdPlanningRessource
                        WHERE PE.IdPlanningEvenement IN (:eventIds)";

            try {
                // PARAM_INT_ARRAY permet à Doctrine de gérer le tableau (IN (1, 2, 3))
                $typesTrouves = $this->connection->fetchAllAssociative(
                    $sqlBulk,
                    ['eventIds' => $eventIds],
                );

                // On vérifie les droits pour chaque type trouvé dans le lot
                foreach ($typesTrouves as $type) {
                    if ($type == 'Projet' && !in_array($level, [22, 23])) return false;
                    if (in_array($type, ['Paie', 'Rubrique Perso']) && $level !== 23) return false;
                }

                return true; // Tous les types sont autorisés !

            } catch (Exception $e) {
                return false;
            }
        }


        // =========================================================
        // 3. CAS CLASSIQUE : Objet Unique ou ID de ressource (CREATE, UPDATE, DELETE)
        // =========================================================

        $idRessource = null;

        if (is_numeric($subject)) {
            // Cas de la création : le contrôleur nous a passé l'ID direct
            $idRessource = (int) $subject;
        } elseif ($subject instanceof Planningevenement) {
            // Cas de l'édition : le contrôleur nous a passé l'objet complet
            $idRessource = $subject->getIdplanningressource();
        }

        if (!$idRessource) {
            return false;
        }

        // Seulement maintenant, on interroge le type de la ressource !
        $sqlType = "SELECT
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
            $type = $this->connection->fetchOne($sqlType, ['idRessource' => $idRessource]);
        } catch (Exception $e) {
            return false;
        }

        // 4. Vérification des actions SPÉCIFIQUES à un objet
        switch ($attribute) {
            case self::CREATE:
            case self::UPDATE:
            case self::DELETE:
            case self::LOCK:
                if ($type === 'Projet') return $level === 22 || $level === 23;
                if ($type === 'Paie' || $type === 'Rubrique Perso') return $level === 23;
                return false;

        }

        return false;
    }
}
