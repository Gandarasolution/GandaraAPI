<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'Planning')]
class Planning
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'IdPlanning', type: 'bigint')]
    private int $idplanning;

    #[ORM\Column(name: 'NomPlanning', type: 'string', nullable: true, length: 100)]
    private ?string $nomplanning = null;

    #[ORM\ManyToOne(targetEntity: Image::class)]
    #[ORM\JoinColumn(name: 'IdPlanningImage', referencedColumnName: 'IdImage')]
    private ?Image $idplanningimage = null;

    public function getIdplanning(): int
    {
        return $this->idplanning;
    }

    public function setIdplanning(int $idplanning): static
    {
        $this->idplanning = $idplanning;
        return $this;
    }

    public function getNomplanning(): ?string
    {
        return $this->nomplanning;
    }

    public function setNomplanning(?string $nomplanning): static
    {
        $this->nomplanning = $nomplanning;
        return $this;
    }

    public function getIdplanningimage(): ?Image
    {
        return $this->idplanningimage;
    }

    public function setIdplanningimage(?int $idplanningimage): static
    {
        $this->idplanningimage = $idplanningimage;
        return $this;
    }
}
