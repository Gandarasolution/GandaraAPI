<?php

namespace App\Security\Voter;

use App\Entity\Session;
use App\Service\PlanningPermissionHelper;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class VueChantierVoter extends Voter
{
    public const CHANTIER_ADD    = 'CHANTIER_VUE_ADD';
    public const CHANTIER_MANAGE = 'CHANTIER_MANAGE';
    public const CHANTIER_DELETE = 'CHANTIER_DELETE';
    public const VIEW            = 'CHANTIER_VUE_VIEW';

    /**
     * Correspondance entre les autorisations Symfony, l'identifiant du droit SQL et le niveau requis.
     */
    private const DROITS = [
        self::VIEW => [
            'idDroit' => 214,
            'niveau'  => 22,
        ],
        self::CHANTIER_ADD => [
            'idDroit' => 220,
            'niveau'  => 3,
        ],
        self::CHANTIER_MANAGE => [
            'idDroit' => 220,
            'niveau'  => 3,
        ],
        self::CHANTIER_DELETE => [
            'idDroit' => 220,
            'niveau'  => 3,
        ],
    ];

    public function __construct(
        private readonly Connection $connection,
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


        $statutAcces = null;

        try {
            if ($attribute !== self::CHANTIER_ADD) {
                // 1. Récupération du statut de la vue (0: Publique, 1: Privée autorisée, 2: Privée refusée)
                $sqlVue = '
                    SELECT
                        CASE
                            WHEN COUNT(*) = 0 THEN 0
                            WHEN COUNT(CASE WHEN IdSession = :idSession THEN 1 END) > 0 THEN 1
                            ELSE 2
                        END AS StatutAcces
                    FROM PlanningChantierVueAttribution
                    WHERE IdPlanningChantierVue = :idPlanningChantierVue;
                ';

                $statutAcces = (int) $this->connection->fetchOne($sqlVue, [
                    'idSession'             => $user->getIdpersonnel(),
                    'idPlanningChantierVue' => (int) $subject,
                ]);

                // Vue privée sans attribution -> refus strict
                if ($statutAcces === 2) {
                    return false;
                }

                // Vue privée avec attribution -> accès direct
                if ($statutAcces === 1) {
                    return true;
                }
            }
        } catch (Exception $e) {
            $this->logger->error('Erreur récupération attribution vue dans VueChantierVoter', [
                'attribute'   => $attribute,
                'idPersonnel' => $user->getIdpersonnel(),
                'exception'   => $e->getMessage(),
            ]);

            return false;
        }

        // 2. Vue publique (ou ajout) : vérification via PlanningPermissionHelper
        $configDroit = self::DROITS[$attribute];
        $permissionDetails = $this->permissionHelper->getPermissionDetails(
            $user,
            $configDroit['idDroit'],
            $configDroit['niveau']
        );

        $this->logger->debug('VueChantierVoter - Vérification du droit', [
            'attribute'    => $attribute,
            'idPersonnel'  => $user->getIdpersonnel(),
            'idDroit'      => $configDroit['idDroit'],
            'niveau'       => $permissionDetails['level'],
            'niveauRequis' => $configDroit['niveau'],
            'hasRight'     => $permissionDetails['hasRight'],
            'statutAcces'  => $statutAcces,
        ]);

        return $permissionDetails['hasRight'];
    }
}
