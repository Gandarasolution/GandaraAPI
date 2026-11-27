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
    public const CREATE = 'RESSOURCE_PERSONNALISE_CREATE';
    public const MANAGE_PERMISSIONS = 'MANAGE_PERMISSIONS';

    public const EDIT_IMAGE_CHANTIER = 'RESOURCE_EDIT_IMAGE_CHANTIER';
    public const EDIT_IMAGE_RUBRIQUE = 'RESOURCE_EDIT_IMAGE_RUBRIQUE';

    private const DROITS_SPECIFIQUES = [
        self::CREATE => [
            'idDroit' => PlanningPermissionHelper::DROIT_RUBRIQUE_PERSO,
            'niveau'  => PlanningPermissionHelper::NIVEAU_ECRITURE,
        ],
        self::EDIT_IMAGE_CHANTIER => [
            'idDroit' => PlanningPermissionHelper::DROIT_PROJET,
            'niveau'  => PlanningPermissionHelper::NIVEAU_ECRITURE,
        ],
        self::EDIT_IMAGE_RUBRIQUE => [
            'idDroit' => PlanningPermissionHelper::DROIT_PROJET,
            'niveau'  => PlanningPermissionHelper::NIVEAU_ECRITURE,
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



        // 1. Droits spécifiques (images chantier / rubrique)
        if (isset(self::DROITS_SPECIFIQUES[$attribute])) {
            $configDroit = self::DROITS_SPECIFIQUES[$attribute];
            $permissionDetails = $this->permissionHelper->getPermissionDetails(
                $user,
                $configDroit['idDroit'],
                $configDroit['niveau']
            );

            return $permissionDetails['hasRight'];
        }

        // 2. Création et gestion des permissions (droit standard 214 niveau 23)
        if (in_array($attribute, [self::CREATE, self::MANAGE_PERMISSIONS], true)) {
            $level = $this->permissionHelper->getUserLevel($user);
            return $level === 23;
        }

        if (!$subject instanceof Planningressource) {
            return false;
        }

        // 3. Vérification selon le type de ressource (232 = Projet, 231 = Paie, 233 = Rubrique Perso)
        $requiredLevel = match ($attribute) {
            self::VIEW, self::SEARCH => PlanningPermissionHelper::NIVEAU_LECTURE,
            self::EDIT               => PlanningPermissionHelper::NIVEAU_ECRITURE,
            default                  => null,
        };

        if ($requiredLevel === null) {
            return false;
        }

        $type = $this->permissionHelper->getResourceType($subject->getIdplanningressource());

        return $this->permissionHelper->canAccessResource($user, $type, $requiredLevel);
    }
}
