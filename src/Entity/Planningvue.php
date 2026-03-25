<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'PlanningVue')]
class Planningvue
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'IdPlanningVue', type: 'bigint')]
    private int $idplanningvue;

    #[ORM\Column(name: 'LibellePlanningVue', type: 'string', nullable: true, length: 100)]
    private ?string $libelleplanningvue = null;

    #[ORM\Column(name: 'DescriptionPlanningVue', type: 'string', nullable: true, length: 200)]
    private ?string $descriptionplanningvue = null;

    #[ORM\Column(name: 'ChampsPremierGroupePlanningVue', type: 'string', nullable: true, length: 100)]
    private ?string $champspremiergroupeplanningvue = null;

    #[ORM\Column(name: 'ChampsDeuxiemeGroupePlanningVue', type: 'string', nullable: true, length: 100)]
    private ?string $champsdeuxiemegroupeplanningvue = null;

    #[ORM\ManyToOne(targetEntity: Image::class)]
    #[ORM\JoinColumn(name: 'IdPlanningImage', referencedColumnName: 'IdImage', nullable: true)]
    private ?Image $idplanningimage = null;

    public function getIdplanningvue(): int
    {
        return $this->idplanningvue;
    }

    public function setIdplanningvue(int $idplanningvue): static
    {
        $this->idplanningvue = $idplanningvue;
        return $this;
    }

    public function getLibelleplanningvue(): ?string
    {
        return $this->libelleplanningvue;
    }

    public function setLibelleplanningvue(?string $libelleplanningvue): static
    {
        $this->libelleplanningvue = $libelleplanningvue;
        return $this;
    }

    public function getDescriptionplanningvue(): ?string
    {
        return $this->descriptionplanningvue;
    }

    public function setDescriptionplanningvue(?string $descriptionplanningvue): static
    {
        $this->descriptionplanningvue = $descriptionplanningvue;
        return $this;
    }

    public function getChampspremiergroupeplanningvue(): ?string
    {
        return $this->champspremiergroupeplanningvue;
    }

    public function setChampspremiergroupeplanningvue(?string $champspremiergroupeplanningvue): static
    {
        $this->champspremiergroupeplanningvue = $champspremiergroupeplanningvue;
        return $this;
    }

    public function getChampsdeuxiemegroupeplanningvue(): ?string
    {
        return $this->champsdeuxiemegroupeplanningvue;
    }

    public function setChampsdeuxiemegroupeplanningvue(?string $champsdeuxiemegroupeplanningvue): static
    {
        $this->champsdeuxiemegroupeplanningvue = $champsdeuxiemegroupeplanningvue;
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
}
