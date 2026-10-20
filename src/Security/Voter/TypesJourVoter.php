<?php

namespace App\Security\Voter;

use App\Entity\Session;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use \Symfony\Component\Security\Core\Authorization\Voter\Vote;


class TypesJourVoter extends Voter
{
    public const CREATE = 'TYPES_JOUR_CREATE';
    public const EDIT = 'TYPES_JOUR_EDIT';
    public const DELETE = 'TYPES_JOUR_DELETE';
    public const VIEW = 'TYPES_JOUR_VIEW';

    public function __construct(private Connection $connection, private LoggerInterface $logger) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        $this->logger->debug("Voter TypesJourVoter supports called with attribute: $attribute");
        return in_array($attribute, [self::VIEW, self::EDIT, self::CREATE, self::DELETE]);
    }


    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();


        if (!$user instanceof Session) {
            return false;
        }

        // Récupération du niveau de l'utilisateur
        $sql = 'EXEC ps_PlanningDroitSelect @IdPersonnel = :id';
        try {
            $planningDroit = $this->connection->fetchAssociative($sql, ['id' => $user->getIdpersonnel()]);
        } catch (Exception $e) {
            return false;
        }
        $level = (int)($planningDroit['IdDroitNiveau'] ?? 21);

        switch ($attribute) {
            case self::CREATE:
                return $level === 23;
            case self::EDIT:
                return in_array($level, [22, 23]);
            case self::DELETE:
                return $level === 23;
            case self::VIEW:
                return in_array($level, [22, 23]);
            default:
                return false;
        }
    }
}
