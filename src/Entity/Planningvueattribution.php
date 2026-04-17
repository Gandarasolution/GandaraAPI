<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'PlanningVueAttribution')]
class Planningvueattribution
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'IdPlanningVueAttribution', type: 'bigint')]
    private int $idplanningvueattribution;

    #[ORM\ManyToOne(targetEntity: Planningvue::class)]
    #[ORM\JoinColumn(name: 'IdPlanningVue', referencedColumnName: 'IdPlanningVue')]
    private Planningvue $idplanningvue;

    #[ORM\ManyToOne(targetEntity: Session::class)]
    #[ORM\JoinColumn(name: 'IdSession', referencedColumnName: 'IdPersonnel')]
    private Session $idplanninguser;

    public function getIdplanningvueattribution(): int
    {
        return $this->idplanningvueattribution;
    }

    public function setIdplanningvueattribution(int $idplanningvueattribution): static
    {
        $this->idplanningvueattribution = $idplanningvueattribution;
        return $this;
    }

    public function getIdplanningvue(): ?int
    {
        return $this->idplanningvue;
    }

    public function setIdplanningvue(?int $idplanningvue): static
    {
        $this->idplanningvue = $idplanningvue;
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
}
