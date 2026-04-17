<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'PlanningVueRelation')]
class Planningvuerelation
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Planning::class)]
    #[ORM\JoinColumn(name: 'IdPlanning', referencedColumnName: 'IdPlanning')]
    private int $idplanning;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Planningvue::class)]
    #[ORM\JoinColumn(name: 'IdPlanningVue', referencedColumnName: 'IdPlanningVue')]
    private int $idplanningvue;

    public function getIdplanning(): int
    {
        return $this->idplanning;
    }

    public function setIdplanning(int $idplanning): static
    {
        $this->idplanning = $idplanning;
        return $this;
    }

    public function getIdplanningvue(): int
    {
        return $this->idplanningvue;
    }

    public function setIdplanningvue(int $idplanningvue): static
    {
        $this->idplanningvue = $idplanningvue;
        return $this;
    }
}
