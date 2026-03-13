<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'PlanningJourNontravaille')]
class Planningjournontravaille
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'IdPlanningJourNontravaille', type: 'bigint')]
    private int $idplanningjournontravaille;

    #[ORM\Column(name: 'DatePlanningJourNontravaille', type: 'date', nullable: true)]
    private ?\DateTimeInterface $dateplanningjournontravaille = null;

    #[ORM\Column(name: 'IdPlanning', type: 'bigint', nullable: true)]
    private ?int $idplanning = null;

    public function getIdplanningjournontravaille(): int
    {
        return $this->idplanningjournontravaille;
    }

    public function setIdplanningjournontravaille(int $idplanningjournontravaille): static
    {
        $this->idplanningjournontravaille = $idplanningjournontravaille;
        return $this;
    }

    public function getDateplanningjournontravaille(): ?\DateTimeInterface
    {
        return $this->dateplanningjournontravaille;
    }

    public function setDateplanningjournontravaille(?\DateTimeInterface $dateplanningjournontravaille): static
    {
        $this->dateplanningjournontravaille = $dateplanningjournontravaille;
        return $this;
    }

    public function getIdplanning(): ?int
    {
        return $this->idplanning;
    }

    public function setIdplanning(?int $idplanning): static
    {
        $this->idplanning = $idplanning;
        return $this;
    }
}
