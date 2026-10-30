<?php

namespace App\Security\Voter;

use App\Entity\Session;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class VueVoter extends Voter
{
    // Ajout / modification des vues Planning
    public const PLANNING_MANAGE = 'PLANNING_MANAGE';

    // Suppression des vues Planning
    public const PLANNING_DELETE = 'PLANNING_DELETE';

    // Ajout / modification des vues Chantier
    public const CHANTIER_MANAGE = 'CHANTIER_MANAGE';

    // Suppression des vues Chantier
    public const CHANTIER_DELETE = 'CHANTIER_DELETE';


    public const VIEW = 'VUE_VIEW';


    /**
     * Correspondance entre les autorisations Symfony
     * et les identifiants des droits SQL.
     *
     * À remplacer par tes véritables IdDroit.
     */
    private const DROITS = [
        self::VIEW => 214,
        self::PLANNING_MANAGE => 222,
        self::PLANNING_DELETE => 221,
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

            // Récupération du droit correspondant
            $sql = '
                EXEC ps_PlanningDroitSelect
                    @IdPersonnel = :idPersonnel,
                    @IdDroits = :idDroit
            ';

            $droit = $this->connection->fetchAssociative($sql, [
                'idPersonnel' => $user->getIdpersonnel(),
                'idDroit' => (string) $idDroit
            ]);

        } catch (Exception $e) {

            $this->logger->error('Erreur récupération droit VueVoter', [
                'attribute' => $attribute,
                'idPersonnel' => $user->getIdpersonnel(),
                'exception' => $e->getMessage()
            ]);

            return false;
        }

        // L'utilisateur ne possède pas ce droit
        if (!$droit) {
            return false;
        }

        // Récupération des informations du droit
        $niveau = (int) $droit['IdDroitNiveau'];

        $gestionParNiveau = (bool) $droit['GestionParNiveau'];

        $this->logger->debug('VueVoter - Vérification du droit', [
            'attribute' => $attribute,
            'idPersonnel' => $user->getIdpersonnel(),
            'idDroit' => $idDroit,
            'niveau' => $niveau,
            'gestionParNiveau' => $gestionParNiveau
        ]);

        // Si le droit ne nécessite pas de niveau,
        // sa simple présence suffit
        if (!$gestionParNiveau) {
            return true;
        }

        // Vérification du niveau requis
        return $niveau > 22;
    }
}
