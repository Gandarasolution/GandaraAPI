<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'PlanningUserLog')]
class Planninguserlog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'IdPlanningUserLog', type: 'bigint')]
    private int $idplanninguserlog;

    #[ORM\Column(name: 'IdPlanningUser', type: 'bigint', nullable: true)]
    private ?int $idplanninguser = null;

    #[ORM\Column(name: 'LogPlanningUserLog', type: 'string', nullable: true, length: 255)]
    private ?string $logplanninguserlog = null;

    public function getIdplanninguserlog(): int
    {
        return $this->idplanninguserlog;
    }

    public function setIdplanninguserlog(int $idplanninguserlog): static
    {
        $this->idplanninguserlog = $idplanninguserlog;
        return $this;
    }

    public function getIdplanninguser(): ?int
    {
        return $this->idplanninguser;
    }

    public function setIdplanninguser(?int $idplanninguser): static
    {
        $this->idplanninguser = $idplanninguser;
        return $this;
    }

    public function getLogplanninguserlog(): ?string
    {
        return $this->logplanninguserlog;
    }

    public function setLogplanninguserlog(?string $logplanninguserlog): static
    {
        $this->logplanninguserlog = $logplanninguserlog;
        return $this;
    }
}
