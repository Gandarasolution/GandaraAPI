<?php

namespace App\Security\Voter;

use App\Entity\Planningressource;
use App\Entity\Session;
use App\Service\PlanningPermissionHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ResourceVoter extends Voter
{
    public const VIEW = 'RESOURCE_VIEW';
    public const EDIT = 'RESOURCE_EDIT';
    public const SEARCH = 'RESOURCE_SEARCH';
    public const CREATE = 'RESOURCE_CREATE';
    public const MANAGE_PERMISSIONS = 'MANAGE_PERMISSIONS';

    // Nouvel attribut spécifique pour la modification d'image
    public const EDIT_IMAGE_CHANTIER = 'RESOURCE_EDIT_IMAGE_CHANTIER';
    public const EDIT_IMAGE_RUBRIQUE = 'RESOURCE_EDIT_IMAGE_RUBRIQUE';


    /**
     * Mapping des attributs de droits vers leurs IdDroits SQL
     */
    private const DROITS_SPECIFIQUES = [
        self::EDIT_IMAGE_CHANTIER => 225,
        self::EDIT_IMAGE_RUBRIQUE => 226,
    ];

    public function __construct(
        private readonly PlanningPermissionHelper $permissionHelper,
        private readonly LoggerInterface $logger
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (in_array($attribute, [self::CREATE, self::MANAGE_PERMISSIONS], true)) {
            return true;
        }

        return in_array($attribute, [self::VIEW, self::EDIT, self::SEARCH, self::EDIT_IMAGE_CHANTIER, self::EDIT_IMAGE_RUBRIQUE], true)
            && $subject instanceof Planningressource;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof Session) {
            return false;
        }

        // =========================================================
        // 1. CAS SPÉCIFIQUE : ATTRIBUTS AVEC IDDROIT DÉDIÉS (ex: Image 225/226)
        // =========================================================
        if ($attribute === self::EDIT_IMAGE_CHANTIER) {
            $permissionDetails = $this->permissionHelper->getPermissionDetails($user, self::DROITS_SPECIFIQUES[self::EDIT_IMAGE_CHANTIER]);
            return $permissionDetails['hasRight'];
        }

        if ($attribute === self::EDIT_IMAGE_RUBRIQUE) {
            $permissionDetails = $this->permissionHelper->getPermissionDetails($user, self::DROITS_SPECIFIQUES[self::EDIT_IMAGE_RUBRIQUE]);
            return $permissionDetails['hasRight'];
        }

        // =========================================================
        // 2. CAS GÉNÉRATION PAR NIVEAU / TYPE DE RESSOURCE (Droit standard 214)
        // =========================================================
        $defaultPermission = $this->permissionHelper->getPermissionDetails($user, 214);
        $level = $defaultPermission['level'];

        if (in_array($attribute, [self::CREATE, self::MANAGE_PERMISSIONS], true)) {
            return $level === 23;
        }

        if (!$subject instanceof Planningressource) {
            return false;
        }

        $type = $this->permissionHelper->getResourceType($subject->getIdplanningressource());
        return $this->permissionHelper->hasAccessByResourceType($type, $level);
    }
}
