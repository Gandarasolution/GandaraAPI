<?php

namespace App\Security\Voter;

use App\Entity\Session;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class VueChantierVoter extends Voter
{

    //Ajout des vues Chantier
    public const CHANTIER_ADD = 'CHANTIER_VUE_ADD';
    //modification des vues Chantier
    public const CHANTIER_MANAGE = 'CHANTIER_MANAGE';

    // Suppression des vues Chantier
    public const CHANTIER_DELETE = 'CHANTIER_DELETE';

    public const VIEW = 'VUE_VIEW';

    /**
     * Correspondance entre les autorisations Symfony et les identifiants des droits SQL.
     */
    private const DROITS = [
        self::VIEW => 214,
        self::CHANTIER_ADD => 220,
        self::CHANTIER_MANAGE => 220,
        self::CHANTIER_DELETE => 219,
    ];

    public function __construct(
        private Connection $connection,
        private LoggerInterface $logger
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

        // Vérification de la session utilisateur
        if (!$user instanceof Session) {
            return false;
        }

        $idDroit = self::DROITS[$attribute];

        try {
            if ($attribute !== self::CHANTIER_ADD) {
                // 1. Récupération du statut de la vue (Publique, Privée autorisée, Privée refusée)
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

                $statutAcces = $this->connection->fetchOne($sqlVue, [
                    'idSession' => $user->getIdpersonnel(),
                    'idPlanningChantierVue' => (int)$subject
                ]);

                // Si la vue est privée et que l'utilisateur n'y a pas accès -> Accès strictement refusé
                if ($statutAcces === 2) {
                    return false;
                }

                // Si la vue est privée et que l'utilisateur y a accès -> Accès accordé directement
                if ($statutAcces === 1) {
                    return true;
                }
            }

            // 2. Si la vue est Publique, on contrôle les droits globaux SQL de l'utilisateur
            $sqlDroit = '
                EXEC ps_PlanningDroitSelect
                    @IdPersonnel = :idPersonnel,
                    @IdDroits = :idDroit
            ';

            $droit = $this->connection->fetchAssociative($sqlDroit, [
                'idPersonnel' => $user->getIdpersonnel(),
                'idDroit' => (string) $idDroit
            ]);

        } catch (Exception $e) {
            $this->logger->error('Erreur récupération droit VuePlanningVoter', [
                'attribute' => $attribute,
                'idPersonnel' => $user->getIdpersonnel(),
                'exception' => $e->getMessage()
            ]);

            return false;
        }

        // L'utilisateur ne possède pas le droit requis pour une vue publique
        if (!$droit) {
            return false;
        }

        // Récupération des informations du droit
        $niveau = (int) $droit['IdDroitNiveau'];
        $gestionParNiveau = (bool) $droit['GestionParNiveau'];

        $this->logger->debug('VuePlanningVoter - Vérification du droit', [
            'attribute' => $attribute,
            'idPersonnel' => $user->getIdpersonnel(),
            'idDroit' => $idDroit,
            'niveau' => $niveau,
            'gestionParNiveau' => $gestionParNiveau,
            'statutAcces' => $attribute !== self::CHANTIER_ADD ? $statutAcces : null,
        ]);

        if (!$gestionParNiveau) {
            return true;
        }

        // Vérification du niveau requis
        return $niveau > 22;
    }
}
