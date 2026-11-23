<?php

namespace App\Security\Voter;

use App\Entity\Session;
use App\Service\PlanningPermissionHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class VuePlanningVoter extends Voter
{
    public const VIEW   = 'PLANNING_VUE_VIEW';
    public const CREATE = 'PLANNING_VUE_CREATE';
    public const UPDATE = 'PLANNING_VUE_UPDATE';
    public const DELETE = 'PLANNING_VUE_DELETE';


    private const DROITS = [
        self::VIEW => [
            'idDroit' => 221,
            'niveau'  => 2,
        ],
        self::CREATE => [
            'idDroit' => 221,
            'niveau'  => 3,
        ],
        self::UPDATE => [
            'idDroit' => 221,
            'niveau'  => 3,
        ],
        self::DELETE => [
            'idDroit' => 221,
            'niveau'  => 3,
        ],
    ];

    public function __construct(
        private readonly PlanningPermissionHelper $permissionHelper,
        private readonly LoggerInterface $logger
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return isset(self::DROITS[$attribute]);
    }

    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token,
        ?Vote $vote = null
    ): bool {
        $user = $token->getUser();
        if (!$user instanceof Session) {
            return false;
        }

        $configDroit = self::DROITS[$attribute];
        $permissionDetails = $this->permissionHelper->getPermissionDetails(
            $user,
            $configDroit['idDroit'],
            $configDroit['niveau']
        );

        $this->logger->debug('VuePlanningVoter - Vérification du droit', [
            'attribute'    => $attribute,
            'idPersonnel'  => $user->getIdpersonnel(),
            'idDroit'      => $configDroit['idDroit'],
            'niveau'       => $permissionDetails['level'],
            'niveauRequis' => $configDroit['niveau'],
            'hasRight'     => $permissionDetails['hasRight'],
        ]);

        return $permissionDetails['hasRight'];
    }
}
