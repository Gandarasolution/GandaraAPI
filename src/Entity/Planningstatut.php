<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'PlanningStatut')]
class Planningstatut
{
    #[ORM\Id]
    #[ORM\Column(name: 'IdPlanningStatut', type: 'integer')]
    private int $idplanningstatut;

    #[ORM\Column(name: 'LibellePlanningStatut', type: 'string', nullable: true, length: 50)]
    private ?string $libelleplanningstatut = null;

    #[ORM\Column(name: 'CouleurPlanningStatut', type: 'string', nullable: true, length: 50)]
    private ?string $couleurplanningstatut = null;

    public function getIdplanningstatut(): int
    {
        return $this->idplanningstatut;
    }

    public function setIdplanningstatut(int $idplanningstatut): static
    {
        $this->idplanningstatut = $idplanningstatut;
        return $this;
    }

    public function getLibelleplanningstatut(): ?string
    {
        return $this->libelleplanningstatut;
    }

    public function setLibelleplanningstatut(?string $libelleplanningstatut): static
    {
        $this->libelleplanningstatut = $libelleplanningstatut;
        return $this;
    }

    public function getCouleurplanningstatut(): ?string
    {
        return $this->couleurplanningstatut;
    }

    public function setCouleurplanningstatut(?string $couleurplanningstatut): static
    {
        $this->couleurplanningstatut = $couleurplanningstatut;
        return $this;
    }
}
