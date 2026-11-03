<?php

namespace App\Security\Voter;

use App\Entity\Session;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
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

    /**
     * Correspondance entre les autorisations Symfony et les identifiants des droits SQL.
     */
    private const DROITS = [
        self::VIEW_DATE   => 214,
        self::CREATE_DATE => 223,
        self::EDIT_DATE   => 223,
        self::DELETE_DATE => 223,
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
                'idDroit'     => (string) $idDroit,
            ]);
        } catch (Exception $e) {
            $this->logger->error('Erreur récupération droit NonWorkingDateVoter', [
                'attribute'   => $attribute,
                'idPersonnel' => $user->getIdpersonnel(),
                'exception'   => $e->getMessage(),
            ]);

            return false;
        }

        if (!$droit) {
            return false;
        }

        // Récupération des informations du droit
        $niveau          = (int) $droit['IdDroitNiveau'];
        $gestionParNiveau = (bool) $droit['GestionParNiveau'];

        $this->logger->debug('NonWorkingDateVoter - Vérification du droit', [
            'attribute'        => $attribute,
            'idPersonnel'      => $user->getIdpersonnel(),
            'idDroit'          => $idDroit,
            'niveau'           => $niveau,
            'gestionParNiveau' => $gestionParNiveau,
        ]);

        if (!$gestionParNiveau) {
            return true;
        }

        // Vérification du niveau requis pour les droits avec gestion par niveau (ex: VIEW -> 214)
        return $niveau > 22;
    }
}
