<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'PlanningVueRelation')]
class Planningvuerelation
{
    #[ORM\Id]
    #[ORM\Column(name: 'IdPlanning', type: 'bigint')]
    private int $idplanning;

    #[ORM\Id]
    #[ORM\Column(name: 'IdPlanningVue', type: 'bigint')]
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
