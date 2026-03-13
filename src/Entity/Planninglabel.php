<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'PlanningLabel')]
class Planninglabel
{
    #[ORM\Id]
    #[ORM\Column(name: 'IdPlanningLabel', type: 'integer')]
    private int $idplanninglabel;

    #[ORM\Column(name: 'LibellePlanningLabel', type: 'string', nullable: true, length: 50)]
    private ?string $libelleplanninglabel = null;

    #[ORM\Column(name: 'CouleurPlanningLabel', type: 'string', nullable: true, length: 50)]
    private ?string $couleurplanninglabel = null;

    public function getIdplanninglabel(): int
    {
        return $this->idplanninglabel;
    }

    public function setIdplanninglabel(int $idplanninglabel): static
    {
        $this->idplanninglabel = $idplanninglabel;
        return $this;
    }

    public function getLibelleplanninglabel(): ?string
    {
        return $this->libelleplanninglabel;
    }

    public function setLibelleplanninglabel(?string $libelleplanninglabel): static
    {
        $this->libelleplanninglabel = $libelleplanninglabel;
        return $this;
    }

    public function getCouleurplanninglabel(): ?string
    {
        return $this->couleurplanninglabel;
    }

    public function setCouleurplanninglabel(?string $couleurplanninglabel): static
    {
        $this->couleurplanninglabel = $couleurplanninglabel;
        return $this;
    }
}
