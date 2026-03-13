<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'HistoriquePlanningVue')]
class Historiqueplanningvue
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'IdHistorique', type: 'bigint')]
    private int $idhistorique;

    #[ORM\Column(name: 'TypeHistorique', type: 'bigint', nullable: true)]
    private ?int $typehistorique = null;

    #[ORM\Column(name: 'IdPersonnel', type: 'bigint', nullable: true)]
    private ?int $idpersonnel = null;

    #[ORM\Column(name: 'DateHistorique', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $datehistorique = null;

    #[ORM\Column(name: 'IdDocument', type: 'bigint', nullable: true)]
    private ?int $iddocument = null;

    #[ORM\Column(name: 'Commentaire', type: 'string', nullable: true, length: 255)]
    private ?string $commentaire = null;

    public function getIdhistorique(): int
    {
        return $this->idhistorique;
    }

    public function setIdhistorique(int $idhistorique): static
    {
        $this->idhistorique = $idhistorique;
        return $this;
    }

    public function getTypehistorique(): ?int
    {
        return $this->typehistorique;
    }

    public function setTypehistorique(?int $typehistorique): static
    {
        $this->typehistorique = $typehistorique;
        return $this;
    }

    public function getIdpersonnel(): ?int
    {
        return $this->idpersonnel;
    }

    public function setIdpersonnel(?int $idpersonnel): static
    {
        $this->idpersonnel = $idpersonnel;
        return $this;
    }

    public function getDatehistorique(): ?\DateTimeInterface
    {
        return $this->datehistorique;
    }

    public function setDatehistorique(?\DateTimeInterface $datehistorique): static
    {
        $this->datehistorique = $datehistorique;
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

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;
        return $this;
    }
}
