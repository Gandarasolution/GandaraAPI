<?php

namespace App\Security\Voter;

use App\Entity\Session;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
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
     * Correspondance entre les autorisations Symfony et les identifiants des droits SQL.
     */
    private const DROITS = [
        self::VIEW   => 214,
        self::CREATE => 224,
        self::EDIT   => 224,
        self::DELETE => 224,
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
            $this->logger->error('Erreur récupération droit TypesJourVoter', [
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

        $this->logger->debug('TypesJourVoter - Vérification du droit', [
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
