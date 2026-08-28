<?php

namespace App\Security\Voter;

use App\Entity\Planningvue;
use App\Entity\Session;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class VueVoter extends Voter
{

    public const VIEW = 'VUE_VIEW';
    public const EDIT = 'VUE_EDIT';
    public const LOCK = 'VUE_LOCK';

    public const CREATE = 'VUE_CREATE';



    public function __construct(private Connection $connection, private LoggerInterface $logger) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::LOCK, self::CREATE]);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof Session) {
            return false;
        }

        // 1. On récupère le niveau de l'utilisateur (on en a toujours besoin)
        $sql = 'EXEC ps_PlanningDroitSelect @IdPersonnel = :id';
        try {
            $planningDroit = $this->connection->fetchAssociative($sql, ['id' => $user->getIdpersonnel()]);
        } catch (Exception $e) {
            return false;
        }
        $level = (int)($planningDroit['IdDroitNiveau'] ?? 21);

        $this->logger->debug(sprintf('VueVoter voteOnAttribute: attribute=%s, userId=%d, level=%d', $attribute, $user->getIdpersonnel(), $level));


        switch ($attribute) {
            case self::VIEW:
            case self::CREATE:
            case self::EDIT:
            case self::LOCK:
                return $level === 23;

            default:
                return false;

        }

    }
}
