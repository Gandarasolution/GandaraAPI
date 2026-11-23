<?php

namespace App\Security\Voter;

use App\Entity\Session;
use App\Service\PlanningPermissionHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class EmployeeVoter extends Voter
{
    public const EDIT_EMPLOYEE = 'EDIT_EMPLOYEE';
    public const LIST_PLANNING = 'LIST_PLANNING';
    public const LIST          = 'LIST_EMPLOYEE';

    /**
     * Correspondance entre les autorisations Symfony, l'identifiant du droit SQL et le niveau requis.
     */
    private const DROITS = [
        self::EDIT_EMPLOYEE => [
            'idDroit' => 214,
            'niveau'  => 22,
        ],
        self::LIST_PLANNING => [
            'idDroit' => 214,
            'niveau'  => 22,
        ],
        self::LIST => [
            'idDroit' => 214,
            'niveau'  => 22,
        ],
    ];

    public function __construct(
        private readonly PlanningPermissionHelper $permissionHelper,
        private readonly LoggerInterface $logger
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        $this->logger->debug(isset(self::DROITS[$attribute]));
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

        $this->logger->debug('EmployeeVoter - Vérification du droit', [
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
