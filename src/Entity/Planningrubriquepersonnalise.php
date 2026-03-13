<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'PlanningRubriquePersonnalise')]
class Planningrubriquepersonnalise
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'IdPlanningRubriquePersonnalise', type: 'bigint')]
    private int $idplanningrubriquepersonnalise;

    #[ORM\Column(name: 'LibellePlanningRubriquePersonnalise', type: 'string', nullable: true, length: 100)]
    private ?string $libelleplanningrubriquepersonnalise = null;

    public function getIdplanningrubriquepersonnalise(): int
    {
        return $this->idplanningrubriquepersonnalise;
    }

    public function setIdplanningrubriquepersonnalise(int $idplanningrubriquepersonnalise): static
    {
        $this->idplanningrubriquepersonnalise = $idplanningrubriquepersonnalise;
        return $this;
    }

    public function getLibelleplanningrubriquepersonnalise(): ?string
    {
        return $this->libelleplanningrubriquepersonnalise;
    }

    public function setLibelleplanningrubriquepersonnalise(?string $libelleplanningrubriquepersonnalise): static
    {
        $this->libelleplanningrubriquepersonnalise = $libelleplanningrubriquepersonnalise;
        return $this;
    }
}
