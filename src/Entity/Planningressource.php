<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'PlanningRessource')]
class Planningressource
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'IdPlanningRessource', type: 'bigint')]
    private int $idplanningressource;

    #[ORM\Column(name: 'IdPlanningImage', type: 'bigint', nullable: true)]
    private ?int $idplanningimage = null;

    #[ORM\Column(name: 'CouleurFondPlanningRessource', type: 'string', nullable: true, length: 255)]
    private ?string $couleurfondplanningressource = null;

    #[ORM\Column(name: 'CouleurBordurePlanningRessource', type: 'string', nullable: true, length: 255)]
    private ?string $couleurbordureplanningressource = null;

    #[ORM\Column(name: 'CouleurTextePlanningRessource', type: 'string', nullable: true, length: 255)]
    private ?string $couleurtexteplanningressource = null;

    #[ORM\Column(name: 'IdDocument', type: 'bigint', nullable: true)]
    private ?int $iddocument = null;

    #[ORM\Column(name: 'IdTypeDocument', type: 'bigint', nullable: true)]
    private ?int $idtypedocument = null;

    public function getIdplanningressource(): int
    {
        return $this->idplanningressource;
    }

    public function setIdplanningressource(int $idplanningressource): static
    {
        $this->idplanningressource = $idplanningressource;
        return $this;
    }

    public function getIdplanningimage(): ?int
    {
        return $this->idplanningimage;
    }

    public function setIdplanningimage(?int $idplanningimage): static
    {
        $this->idplanningimage = $idplanningimage;
        return $this;
    }

    public function getCouleurfondplanningressource(): ?string
    {
        return $this->couleurfondplanningressource;
    }

    public function setCouleurfondplanningressource(?string $couleurfondplanningressource): static
    {
        $this->couleurfondplanningressource = $couleurfondplanningressource;
        return $this;
    }

    public function getCouleurbordureplanningressource(): ?string
    {
        return $this->couleurbordureplanningressource;
    }

    public function setCouleurbordureplanningressource(?string $couleurbordureplanningressource): static
    {
        $this->couleurbordureplanningressource = $couleurbordureplanningressource;
        return $this;
    }

    public function getCouleurtexteplanningressource(): ?string
    {
        return $this->couleurtexteplanningressource;
    }

    public function setCouleurtexteplanningressource(?string $couleurtexteplanningressource): static
    {
        $this->couleurtexteplanningressource = $couleurtexteplanningressource;
        return $this;
    }

    public function getIddocument(): ?int
    {
        return $this->iddocument;
    }

    public function setIddocument(?int $iddocument): static
    {
        $this->iddocument = $iddocument;
        return $this;
    }

    public function getIdtypedocument(): ?int
    {
        return $this->idtypedocument;
    }

    public function setIdtypedocument(?int $idtypedocument): static
    {
        $this->idtypedocument = $idtypedocument;
        return $this;
    }
}
