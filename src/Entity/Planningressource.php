<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity]
#[ORM\Table(name: 'PlanningRessource')]
#[Assert\Expression(
    "(this.getIdprojet() != null and this.getIdRubrique() == null and this.getIdRubriquePersonnalise() == null)
    or (this.getIdprojet() == null and this.getIdRubrique() != null and this.getIdRubriquePersonnalise() == null)
    or (this.getIdprojet() == null and this.getIdRubrique() == null and this.getIdRubriquePersonnalise() != null)",
    message: "Une ressource de planning doit être liée soit à un projet, soit à une rubrique de paie, soit à une rubrique personnalisée, mais pas à plusieurs en même temps."
)]
class Planningressource
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'IdPlanningRessource', type: 'bigint')]
    private int $idplanningressource;

    #[ORM\ManyToOne(targetEntity: Image::class)]
    #[ORM\JoinColumn(name: 'IdPlanningImage', referencedColumnName: 'IdImage')]
    private ?Image $idplanningimage = null;

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

    #[ORM\OneToOne(targetEntity: Projet::class)]
    #[ORM\JoinColumn(name: 'IdProjet', referencedColumnName: 'IdProjet')]
    private ?Projet $idprojet  = null;

    #[ORM\OneToOne(targetEntity: Socialrubriquepaie::class)]
    #[ORM\JoinColumn(name: 'IdRubrique', referencedColumnName: 'IdSocialRubriquePaie')]
    private ?Socialrubriquepaie $idRubrique  = null;

    #[ORM\OneToOne(targetEntity: Planningrubriquepersonnalise::class)]
    #[ORM\JoinColumn(name: 'IdRubriquePersonnalise', referencedColumnName: 'IdPlanningRubriquePersonnalise')]
    private ?Planningrubriquepersonnalise $idRubriquePersonnalise  = null;


    public function getIdplanningressource(): int
    {
        return $this->idplanningressource;
    }

    public function setIdplanningressource(int $idplanningressource): static
    {
        $this->idplanningressource = $idplanningressource;
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

    /**
     * @return Projet|null
     */
    public function getIdprojet(): ?Projet
    {
        return $this->idprojet;
    }

    /**
     * @return Socialrubriquepaie|null
     */
    public function getIdRubrique(): ?Socialrubriquepaie
    {
        return $this->idRubrique;
    }

    /**
     * @return Planningrubriquepersonnalise|null
     */
    public function getIdRubriquePersonnalise(): ?Planningrubriquepersonnalise
    {
        return $this->idRubriquePersonnalise;
    }
}
