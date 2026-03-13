<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'PlanningEvenement')]
class Planningevenement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'IdPlanningEvenement', type: 'bigint')]
    private int $idplanningevenement;

    #[ORM\Column(name: 'IdTypeDocumentAffectation', type: 'bigint', nullable: true)]
    private ?int $idtypedocumentaffectation = null;

    #[ORM\Column(name: 'IdDocumentAffectation', type: 'bigint', nullable: true)]
    private ?int $iddocumentaffectation = null;

    #[ORM\Column(name: 'DebutPlanningEvenement', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $debutplanningevenement = null;

    #[ORM\Column(name: 'FinPlanningEvenement', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $finplanningevenement = null;

    #[ORM\Column(name: 'AnnotationPlanningEvenement', type: 'string', nullable: true, length: 255)]
    private ?string $annotationplanningevenement = null;

    #[ORM\Column(name: 'IdPlanningRessource', type: 'bigint', nullable: true)]
    private ?int $idplanningressource = null;

    #[ORM\Column(name: 'IdPlanningEtiquette', type: 'bigint', nullable: true)]
    private ?int $idplanningetiquette = null;

    public function getIdplanningevenement(): int
    {
        return $this->idplanningevenement;
    }

    public function setIdplanningevenement(int $idplanningevenement): static
    {
        $this->idplanningevenement = $idplanningevenement;
        return $this;
    }

    public function getIdtypedocumentaffectation(): ?int
    {
        return $this->idtypedocumentaffectation;
    }

    public function setIdtypedocumentaffectation(?int $idtypedocumentaffectation): static
    {
        $this->idtypedocumentaffectation = $idtypedocumentaffectation;
        return $this;
    }

    public function getIddocumentaffectation(): ?int
    {
        return $this->iddocumentaffectation;
    }

    public function setIddocumentaffectation(?int $iddocumentaffectation): static
    {
        $this->iddocumentaffectation = $iddocumentaffectation;
        return $this;
    }

    public function getDebutplanningevenement(): ?\DateTimeInterface
    {
        return $this->debutplanningevenement;
    }

    public function setDebutplanningevenement(?\DateTimeInterface $debutplanningevenement): static
    {
        $this->debutplanningevenement = $debutplanningevenement;
        return $this;
    }

    public function getFinplanningevenement(): ?\DateTimeInterface
    {
        return $this->finplanningevenement;
    }

    public function setFinplanningevenement(?\DateTimeInterface $finplanningevenement): static
    {
        $this->finplanningevenement = $finplanningevenement;
        return $this;
    }

    public function getAnnotationplanningevenement(): ?string
    {
        return $this->annotationplanningevenement;
    }

    public function setAnnotationplanningevenement(?string $annotationplanningevenement): static
    {
        $this->annotationplanningevenement = $annotationplanningevenement;
        return $this;
    }

    public function getIdplanningressource(): ?int
    {
        return $this->idplanningressource;
    }

    public function setIdplanningressource(?int $idplanningressource): static
    {
        $this->idplanningressource = $idplanningressource;
        return $this;
    }

    public function getIdplanningetiquette(): ?int
    {
        return $this->idplanningetiquette;
    }

    public function setIdplanningetiquette(?int $idplanningetiquette): static
    {
        $this->idplanningetiquette = $idplanningetiquette;
        return $this;
    }
}
