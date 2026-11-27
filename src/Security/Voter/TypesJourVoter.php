<?php

namespace App\Security\Voter;

use App\Entity\Session;
use App\Service\PlanningPermissionHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class TypesJourVoter extends Voter
{
    public const CREATE = 'TYPES_JOUR_CREATE';
    public const EDIT   = 'TYPES_JOUR_EDIT';
    public const DELETE = 'TYPES_JOUR_DELETE';
    public const VIEW   = 'TYPES_JOUR_VIEW';

    /**
     * Correspondance entre les autorisations Symfony, l'identifiant du droit SQL et le niveau requis.
     */
    private const DROITS = [
        self::VIEW => [
            'idDroit' => 224,
            'niveau'  => 2,
        ],
        self::CREATE => [
            'idDroit' => 224,
            'niveau'  => 3,
        ],
        self::EDIT => [
            'idDroit' => 224,
            'niveau'  => 3,
        ],
        self::DELETE => [
            'idDroit' => 224,
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

        $this->logger->debug('TypesJourVoter - Vérification du droit', [
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
