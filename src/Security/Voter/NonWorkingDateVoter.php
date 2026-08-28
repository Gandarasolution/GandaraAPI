<?php

namespace App\Security\Voter;

use App\Entity\Planningjournontravaille;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class NonWorkingDateVoter extends Voter
{

    public const EDIT_DATE = 'VUE_EDIT_DATE';
    public const CREATE_DATE = 'VUE_CREATE_DATE';


    public function __construct(private Connection $connection, private LoggerInterface $logger) {}


    protected function supports(string $attribute, mixed $subject): bool
    {
        if ($attribute === self::CREATE_DATE) {
            return true;
        }

        return $attribute == self::EDIT_DATE
            && $subject instanceof Planningjournontravaille;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof \App\Entity\Session) {
            return false;
        }

        $sql = 'EXEC ps_PlanningDroitSelect @IdPersonnel = :id';
        try {
            $planningDroit = $this->connection->fetchAssociative($sql, ['id' => $user->getIdpersonnel()]);
        } catch (Exception $e) {
            return false;
        }
        $level = (int)($planningDroit['IdDroitNiveau'] ?? 21);

        return match ($attribute) {
            self::EDIT_DATE, self::CREATE_DATE => $level === 23,
            default => false,
        };
    }
}
