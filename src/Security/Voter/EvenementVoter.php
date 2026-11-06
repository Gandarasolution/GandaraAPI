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
    public const VIEW_ALL = 'VIEW_ALL';
    public const LOCK = 'EVENEMENT_LOCK';

    public function __construct(
        private PlanningPermissionHelper $permissionHelper,
        private Connection $connection
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (in_array($attribute, [self::VIEW_ALL, self::LOCK], true)) return true;
        if (in_array($attribute, [self::CREATE, self::REPEAT], true)) return is_numeric($subject);
        if ($attribute === self::MASS_DELETE) return is_array($subject);

        return in_array($attribute, [self::UPDATE, self::DELETE], true)
            && $subject instanceof Planningevenement;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof Session) {
            return false;
        }

        $level = $this->permissionHelper->getUserLevel($user);

        // 1. Actions globales
        if ($attribute === self::VIEW_ALL) {
            return in_array($level, [22, 23], true);
        }

        // 2. Suppression en masse
        if ($attribute === self::MASS_DELETE) {
            return $this->voteOnMassDelete($subject, $level);
        }

        // 3. Extraction de l'IdRessource
        $idRessource = match (true) {
            is_numeric($subject) => (int) $subject,
            $subject instanceof Planningevenement => $subject->getIdplanningressource()?->getIdplanningressource(),
            default => null,
        };

        if (!$idRessource) {
            return false;
        }

        // 4. Verification dynamique selon le type de ressource
        $type = $this->permissionHelper->getResourceType($idRessource);
        return $this->permissionHelper->hasAccessByResourceType($type, $level);
    }

    private function voteOnMassDelete(array $eventIds, int $level): bool
    {
        if (empty($eventIds)) return true;

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
            $types = $this->connection->fetchAllAssociative($sqlBulk, ['eventIds' => $eventIds]);
            foreach ($types as $row) {
                if (!$this->permissionHelper->hasAccessByResourceType($row['TypeRessource'], $level)) {
                    return false;
                }
            }
            return true;
        } catch (Exception) {
            return false;
        }
    }
}
