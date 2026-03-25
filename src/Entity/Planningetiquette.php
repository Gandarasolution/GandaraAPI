<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'PlanningEtiquette')]
class Planningetiquette
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'IdPlanningEtiquette', type: 'bigint')]
    private int $idplanningetiquette;

    #[ORM\Column(name: 'LibelleLongPlanningEtiquette', type: 'string', nullable: true, length: 50)]
    private ?string $libellelongplanningetiquette = null;

    #[ORM\Column(name: 'LibelleCourtPlanningEtiquette', type: 'string', nullable: true, length: 10)]
    private ?string $libellecourtplanningetiquette = null;

    #[ORM\ManyToOne(targetEntity: Planningressource::class)]
    #[ORM\JoinColumn(name: 'IdPlanningRessource',referencedColumnName: 'IdPlanningRessource', nullable: false)]
    private ?Planningressource $idplanningressource = null;

    public function getIdplanningetiquette(): int
    {
        return $this->idplanningetiquette;
    }

    public function setIdplanningetiquette(int $idplanningetiquette): static
    {
        $this->idplanningetiquette = $idplanningetiquette;
        return $this;
    }

    public function getLibellelongplanningetiquette(): ?string
    {
        return $this->libellelongplanningetiquette;
    }

    public function setLibellelongplanningetiquette(?string $libellelongplanningetiquette): static
    {
        $this->libellelongplanningetiquette = $libellelongplanningetiquette;
        return $this;
    }

    public function getLibellecourtplanningetiquette(): ?string
    {
        return $this->libellecourtplanningetiquette;
    }

    public function setLibellecourtplanningetiquette(?string $libellecourtplanningetiquette): static
    {
        $this->libellecourtplanningetiquette = $libellecourtplanningetiquette;
        return $this;
    }

    public function getIdplanningressource(): ?Planningressource
    {
        return $this->idplanningressource;
    }

    public function setIdplanningressource(?Planningressource $idplanningressource): static
    {
        $this->idplanningressource = $idplanningressource;
        return $this;
    }
}
