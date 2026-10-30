<?php

namespace App\Security\Voter;

use App\Entity\Session;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Doctrine\DBAL\Exception;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class EmployeeVoter extends Voter
{

    public const EDIT_EMPLOYEE = 'EDIT_EMPLOYEE';
    public const LIST_PLANNING = 'LIST_PLANNING';

    public const LIST = 'LIST_EMPLOYEE';

    public function __construct(private Connection $connection, private LoggerInterface $logger) {}


    protected function supports(string $attribute, mixed $subject): bool
    {
        if (in_array($attribute, [self::EDIT_EMPLOYEE, self::LIST_PLANNING, self::LIST])) {
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

        $sql = 'EXEC ps_PlanningDroitSelect @IdPersonnel = :id, @IdDroits = 214';
        try {
            $planningDroit = $this->connection->fetchAssociative($sql, ['id' => $user->getIdpersonnel()]);
        } catch (Exception $e) {
            return false;
        }
        $level = (int)($planningDroit['IdDroitNiveau'] ?? 21);

        return match ($attribute) {
            self::EDIT_EMPLOYEE, self::LIST_PLANNING, self::LIST => in_array($level, [22, 23]),
            default => false,
        };
    }
}
