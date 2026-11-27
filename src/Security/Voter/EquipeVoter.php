<?php

namespace App\Security\Voter;

use App\Entity\Session;
use App\Service\PlanningPermissionHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class EquipeVoter extends Voter
{
    public const VIEW   = 'EQUIPE_VIEW';
    public const CREATE = 'EQUIPE_CREATE';
    public const UPDATE = 'EQUIPE_UPDATE';
    public const DELETE = 'EQUIPE_DELETE';

    /**
     * Correspondance entre les autorisations Symfony, l'identifiant du droit SQL et le niveau requis.
     */
    private const DROITS = [
        self::VIEW => [
            'idDroit' => 172,
            'niveau'  => 3,
        ],
        self::CREATE => [
            'idDroit' => 172,
            'niveau'  => 3,
        ],
        self::UPDATE => [
            'idDroit' => 172,
            'niveau'  => 3,
        ],
        self::DELETE => [
            'idDroit' => 172,
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

        if ($user->getIdpersonnel() === -1) {
            // L'utilisateur est un administrateur, il a tous les droits
            return true;
        }

        $configDroit = self::DROITS[$attribute];
        $permissionDetails = $this->permissionHelper->getPermissionDetails(
            $user,
            $configDroit['idDroit'],
            $configDroit['niveau']
        );

        $this->logger->debug('EquipeVoter - Vérification du droit', [
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
