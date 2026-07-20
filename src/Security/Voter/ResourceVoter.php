<?php

namespace App\Security\Voter;

use App\Entity\Planningressource;
use App\Entity\Session;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use \Symfony\Component\Security\Core\Authorization\Voter\Vote;

class ResourceVoter extends Voter
{
    // On définit des "actions" possibles
    public const VIEW = 'RESOURCE_VIEW';
    public const EDIT = 'RESOURCE_EDIT';
    public const SEARCH = 'RESOURCE_SEARCH';

    public const CREATE = 'RESOURCE_CREATE';

    public const MANAGE_PERMISSIONS = 'MANAGE_PERMISSIONS';



    public function __construct(private Connection $connection, private LoggerInterface $logger) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        if ($attribute === 'RESOURCE_CREATE' || $attribute === 'MANAGE_PERMISSIONS') {
            return true;
        }

        // Ce voter ne s'active QUE si on demande l'action VIEW ou EDIT sur un objet Ressource
        return in_array($attribute, [self::VIEW, self::EDIT, self::SEARCH])
            && $subject instanceof PlanningRessource; // Vérifie que c'est bien l'entité attendue
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
        if ($attribute === self::CREATE || $attribute === self::MANAGE_PERMISSIONS) {
            return $level === 23;
        }

        // 3. À partir d'ici, on a OBLIGATOIREMENT besoin de l'objet ressource
        if (!$subject instanceof PlanningRessource) {
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
            $type = $this->connection->fetchOne($sqlType, ['idRessource' => $subject->getIdplanningressource()]);
        } catch (Exception $e) {
            return false;
        }

        // 4. Vérification des actions SPÉCIFIQUES à un objet
        switch ($attribute) {
            case self::VIEW:
            case self::SEARCH:
            case self::EDIT:
                if ($type === 'Projet') return $level === 22 || $level === 23;
                if ($type === 'Paie' || $type === 'Rubrique Perso') return $level === 23;
                return false;
        }

        return false;
    }
}
