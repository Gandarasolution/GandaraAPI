<?php

namespace App\Security\Voter;

use App\Entity\Planningevenement;
use App\Entity\Session;
use App\Service\PlanningPermissionHelper;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
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
    public const LOCK = 'EVENEMENT_LOCK';

    public function __construct(
        private readonly PlanningPermissionHelper $permissionHelper,
        private readonly Connection $connection
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (in_array($attribute, [self::CREATE, self::REPEAT], true)) {
            return is_numeric($subject);
        }

        if ($attribute === self::MASS_DELETE) {
            return is_array($subject);
        }

        if ($attribute === self::LOCK) {
            return $subject instanceof Planningevenement || is_numeric($subject);
        }

        return in_array($attribute, [self::UPDATE, self::DELETE], true)
            && $subject instanceof Planningevenement;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {

        $user = $token->getUser();
        if (!$user instanceof Session) {
            return false;
        }

        if ($user->getIdpersonnel() === -1) {
            // L'utilisateur est un administrateur, il a tous les droits
            return true;
        }

        // 1. Suppression en masse : vérification de chaque type de ressource concerné
        if ($attribute === self::MASS_DELETE) {
            return $this->voteOnMassDelete($user, $subject);
        }

        // 2. Extraction de l'IdRessource
        $idRessource = match (true) {
            is_numeric($subject) => (int) $subject,
            $subject instanceof Planningevenement => $subject->getIdplanningressource()?->getIdplanningressource(),
            default => null,
        };

        if (!$idRessource) {
            return false;
        }

        // 3. Vérification en écriture (niveau 3) selon le type de ressource (232 = Projet, 231 = Paie, 233 = Rubrique Perso)
        $type = $this->permissionHelper->getResourceType($idRessource);

        return $this->permissionHelper->canAccessResource(
            $user,
            $type,
            PlanningPermissionHelper::NIVEAU_ECRITURE
        );
    }

    private function voteOnMassDelete(Session $user, array $eventIds): bool
    {
        if (empty($eventIds)) {
            return true;
        }

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
            $types = $this->connection->fetchAllAssociative(
                $sqlBulk,
                ['eventIds' => array_map('intval', $eventIds)],
            );

            foreach ($types as $row) {
                if (!$this->permissionHelper->canAccessResource(
                    $user,
                    $row['TypeRessource'],
                    PlanningPermissionHelper::NIVEAU_ECRITURE
                )) {
                    return false;
                }
            }

            return true;
        } catch (Exception) {
            return false;
        }
    }
}
