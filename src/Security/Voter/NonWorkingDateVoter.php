<?php

namespace App\Security\Voter;

use App\Entity\Session;
use App\Service\PlanningPermissionHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class NonWorkingDateVoter extends Voter
{
    public const VIEW_DATE   = 'VUE_VIEW_DATE';
    public const CREATE_DATE = 'VUE_CREATE_DATE';
    public const EDIT_DATE   = 'VUE_EDIT_DATE';
    public const DELETE_DATE = 'VUE_DELETE_DATE';

    private const DROITS = [
        self::VIEW_DATE => [
            'idDroit' => 223,
            'niveau'  => 2,
        ],
        self::CREATE_DATE => [
            'idDroit' => 223,
            'niveau'  => 3,
        ],
        self::EDIT_DATE => [
            'idDroit' => 223,
            'niveau'  => 3,
        ],
        self::DELETE_DATE => [
            'idDroit' => 223,
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

        $this->logger->debug('NonWorkingDateVoter - Vérification du droit', [
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
