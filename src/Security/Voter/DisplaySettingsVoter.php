<?php

namespace App\Security\Voter;

use App\Entity\Session;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Doctrine\DBAL\Exception;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
class DisplaySettingsVoter extends Voter
{

    public const DISPLAY_SETTINGS_MANAGE = 'DISPLAY_SETTINGS_MANAGE';


    private const DROITS = [
        self::DISPLAY_SETTINGS_MANAGE => 227,
    ];

    public function __construct(private readonly Connection $connection, private LoggerInterface $logger) {}


    protected function supports(string $attribute, mixed $subject): bool
    {
        if ($attribute == self::DISPLAY_SETTINGS_MANAGE) {
            return true;
        }

        return false;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof Session) {
            return false;
        }

        $idDroit = self::DROITS[$attribute];

        $sql = 'EXEC ps_PlanningDroitSelect @IdPersonnel = :id, @IdDroits = :idDroit';
        try {
            $droit = $this->connection->fetchAssociative($sql, ['id' => $user->getIdpersonnel(), 'idDroit' => $idDroit]);
        } catch (Exception $e) {
            $this->logger->error('Erreur récupération droit VuePlanningVoter', [
                'attribute' => $attribute,
                'idPersonnel' => $user->getIdpersonnel(),
                'exception' => $e->getMessage()
            ]);

            return false;
        }

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
        ]);

        if (!$gestionParNiveau) {
            return true;
        }

        return $niveau > 22;

    }


}
