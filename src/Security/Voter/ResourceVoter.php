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

    public const EDIT_IMAGE_CHANTIER = 'RESOURCE_EDIT_IMAGE_CHANTIER';
    public const EDIT_IMAGE_RUBRIQUE = 'RESOURCE_EDIT_IMAGE_RUBRIQUE';

    private const DROITS_SPECIFIQUES = [
        self::EDIT_IMAGE_CHANTIER => [
            'idDroit' => 225,
            'niveau'  => 3,
        ],
        self::EDIT_IMAGE_RUBRIQUE => [
            'idDroit' => 226,
            'niveau'  => 3,
        ],
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

        return in_array($attribute, [
                self::VIEW,
                self::EDIT,
                self::SEARCH,
                self::EDIT_IMAGE_CHANTIER,
                self::EDIT_IMAGE_RUBRIQUE,
            ], true) && $subject instanceof Planningressource;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof Session) {
            return false;
        }

        // 1. Attributs spécifiques (225 / 226) avec niveau requis délégué au helper
        if (isset(self::DROITS_SPECIFIQUES[$attribute])) {
            $configDroit = self::DROITS_SPECIFIQUES[$attribute];
            $permissionDetails = $this->permissionHelper->getPermissionDetails(
                $user,
                $configDroit['idDroit'],
                $configDroit['niveau']
            );

            return $permissionDetails['hasRight'];
        }

        // 2. Cas général par niveau / type de ressource (Droit standard 214)
        $level = $this->permissionHelper->getUserLevel($user);

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
