<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'SocialRubriquePaie')]
class Socialrubriquepaie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'IdSocialRubriquePaie', type: 'bigint')]
    private int $idsocialrubriquepaie;

    #[ORM\Column(name: 'CodeSocialRubriquePaie', type: 'string', nullable: true, length: 20)]
    private ?string $codesocialrubriquepaie = null;

    #[ORM\Column(name: 'DesignationSocialRubriquePaie', type: 'string', nullable: true, length: 100)]
    private ?string $designationsocialrubriquepaie = null;

    #[ORM\Column(name: 'IdSocialRubriquePaieCategorie', type: 'bigint', nullable: true)]
    private ?int $idsocialrubriquepaiecategorie = null;

    #[ORM\Column(name: 'TypeSocialRubriquePaie', type: 'integer', nullable: true)]
    private ?int $typesocialrubriquepaie = null;

    #[ORM\Column(name: 'ValeurSocialRubriquePaie', precision: 18, scale: 2, type: 'decimal', nullable: true)]
    private ?float $valeursocialrubriquepaie = null;

    #[ORM\Column(name: 'DetailImpressionSocialRubriquePaie', type: 'integer', nullable: true)]
    private ?int $detailimpressionsocialrubriquepaie = null;

    #[ORM\Column(name: 'DetailImpressionAnnuelleSocialRubriquePaie', type: 'integer', nullable: true)]
    private ?int $detailimpressionannuellesocialrubriquepaie = null;

    #[ORM\Column(name: 'AffichageExtranetSocialRubriquePaie', type: 'boolean', nullable: true)]
    private ?bool $affichageextranetsocialrubriquepaie = null;

    #[ORM\Column(name: 'AffichageSocialRubriquePaie', type: 'boolean', nullable: true)]
    private ?bool $affichagesocialrubriquepaie = null;

    #[ORM\Column(name: 'ActiveSocialRubriquePaie', type: 'boolean', nullable: true)]
    private ?bool $activesocialrubriquepaie = null;

    #[ORM\Column(name: 'ImpressionSocialRubriquePaie', type: 'boolean', nullable: true)]
    private ?bool $impressionsocialrubriquepaie = null;

    #[ORM\Column(name: 'AffichageVPPlanningSocialRubriquePaie', type: 'boolean', nullable: true)]
    private ?bool $affichagevpplanningsocialrubriquepaie = null;

    #[ORM\Column(name: 'IdSocialTypeRemuneration', type: 'bigint', nullable: true)]
    private ?int $idsocialtyperemuneration = null;

    #[ORM\Column(name: 'IdTypeSocialRubriquePaieChantier', type: 'bigint', nullable: true)]
    private ?int $idtypesocialrubriquepaiechantier = null;

    #[ORM\Column(name: 'Valeur1TypeSocialRubriquePaieChantier', precision: 18, scale: 2, type: 'decimal', nullable: true)]
    private ?float $valeur1typesocialrubriquepaiechantier = null;

    #[ORM\Column(name: 'Valeur2TypeSocialRubriquePaieChantier', precision: 18, scale: 2, type: 'decimal', nullable: true)]
    private ?float $valeur2typesocialrubriquepaiechantier = null;

    #[ORM\Column(name: 'AffichageExtranetInterimSocialRubriquePaie', type: 'boolean', nullable: true)]
    private ?bool $affichageextranetinterimsocialrubriquepaie = null;

    #[ORM\Column(name: 'AffichageInterimSocialRubriquePaie', type: 'boolean', nullable: true)]
    private ?bool $affichageinterimsocialrubriquepaie = null;

    #[ORM\Column(name: 'ActiveInterimSocialRubriquePaie', type: 'boolean', nullable: true)]
    private ?bool $activeinterimsocialrubriquepaie = null;

    public function getIdsocialrubriquepaie(): int
    {
        return $this->idsocialrubriquepaie;
    }

    public function setIdsocialrubriquepaie(int $idsocialrubriquepaie): static
    {
        $this->idsocialrubriquepaie = $idsocialrubriquepaie;
        return $this;
    }

    public function getCodesocialrubriquepaie(): ?string
    {
        return $this->codesocialrubriquepaie;
    }

    public function setCodesocialrubriquepaie(?string $codesocialrubriquepaie): static
    {
        $this->codesocialrubriquepaie = $codesocialrubriquepaie;
        return $this;
    }

    public function getDesignationsocialrubriquepaie(): ?string
    {
        return $this->designationsocialrubriquepaie;
    }

    public function setDesignationsocialrubriquepaie(?string $designationsocialrubriquepaie): static
    {
        $this->designationsocialrubriquepaie = $designationsocialrubriquepaie;
        return $this;
    }

    public function getIdsocialrubriquepaiecategorie(): ?int
    {
        return $this->idsocialrubriquepaiecategorie;
    }

    public function setIdsocialrubriquepaiecategorie(?int $idsocialrubriquepaiecategorie): static
    {
        $this->idsocialrubriquepaiecategorie = $idsocialrubriquepaiecategorie;
        return $this;
    }

    public function getTypesocialrubriquepaie(): ?int
    {
        return $this->typesocialrubriquepaie;
    }

    public function setTypesocialrubriquepaie(?int $typesocialrubriquepaie): static
    {
        $this->typesocialrubriquepaie = $typesocialrubriquepaie;
        return $this;
    }

    public function getValeursocialrubriquepaie(): ?float
    {
        return $this->valeursocialrubriquepaie;
    }

    public function setValeursocialrubriquepaie(?float $valeursocialrubriquepaie): static
    {
        $this->valeursocialrubriquepaie = $valeursocialrubriquepaie;
        return $this;
    }

    public function getDetailimpressionsocialrubriquepaie(): ?int
    {
        return $this->detailimpressionsocialrubriquepaie;
    }

    public function setDetailimpressionsocialrubriquepaie(?int $detailimpressionsocialrubriquepaie): static
    {
        $this->detailimpressionsocialrubriquepaie = $detailimpressionsocialrubriquepaie;
        return $this;
    }

    public function getDetailimpressionannuellesocialrubriquepaie(): ?int
    {
        return $this->detailimpressionannuellesocialrubriquepaie;
    }

    public function setDetailimpressionannuellesocialrubriquepaie(?int $detailimpressionannuellesocialrubriquepaie): static
    {
        $this->detailimpressionannuellesocialrubriquepaie = $detailimpressionannuellesocialrubriquepaie;
        return $this;
    }

    public function getAffichageextranetsocialrubriquepaie(): ?bool
    {
        return $this->affichageextranetsocialrubriquepaie;
    }

    public function setAffichageextranetsocialrubriquepaie(?bool $affichageextranetsocialrubriquepaie): static
    {
        $this->affichageextranetsocialrubriquepaie = $affichageextranetsocialrubriquepaie;
        return $this;
    }

    public function getAffichagesocialrubriquepaie(): ?bool
    {
        return $this->affichagesocialrubriquepaie;
    }

    public function setAffichagesocialrubriquepaie(?bool $affichagesocialrubriquepaie): static
    {
        $this->affichagesocialrubriquepaie = $affichagesocialrubriquepaie;
        return $this;
    }

    public function getActivesocialrubriquepaie(): ?bool
    {
        return $this->activesocialrubriquepaie;
    }

    public function setActivesocialrubriquepaie(?bool $activesocialrubriquepaie): static
    {
        $this->activesocialrubriquepaie = $activesocialrubriquepaie;
        return $this;
    }

    public function getImpressionsocialrubriquepaie(): ?bool
    {
        return $this->impressionsocialrubriquepaie;
    }

    public function setImpressionsocialrubriquepaie(?bool $impressionsocialrubriquepaie): static
    {
        $this->impressionsocialrubriquepaie = $impressionsocialrubriquepaie;
        return $this;
    }

    public function getAffichagevpplanningsocialrubriquepaie(): ?bool
    {
        return $this->affichagevpplanningsocialrubriquepaie;
    }

    public function setAffichagevpplanningsocialrubriquepaie(?bool $affichagevpplanningsocialrubriquepaie): static
    {
        $this->affichagevpplanningsocialrubriquepaie = $affichagevpplanningsocialrubriquepaie;
        return $this;
    }

    public function getIdsocialtyperemuneration(): ?int
    {
        return $this->idsocialtyperemuneration;
    }

    public function setIdsocialtyperemuneration(?int $idsocialtyperemuneration): static
    {
        $this->idsocialtyperemuneration = $idsocialtyperemuneration;
        return $this;
    }

    public function getIdtypesocialrubriquepaiechantier(): ?int
    {
        return $this->idtypesocialrubriquepaiechantier;
    }

    public function setIdtypesocialrubriquepaiechantier(?int $idtypesocialrubriquepaiechantier): static
    {
        $this->idtypesocialrubriquepaiechantier = $idtypesocialrubriquepaiechantier;
        return $this;
    }

    public function getValeur1typesocialrubriquepaiechantier(): ?float
    {
        return $this->valeur1typesocialrubriquepaiechantier;
    }

    public function setValeur1typesocialrubriquepaiechantier(?float $valeur1typesocialrubriquepaiechantier): static
    {
        $this->valeur1typesocialrubriquepaiechantier = $valeur1typesocialrubriquepaiechantier;
        return $this;
    }

    public function getValeur2typesocialrubriquepaiechantier(): ?float
    {
        return $this->valeur2typesocialrubriquepaiechantier;
    }

    public function setValeur2typesocialrubriquepaiechantier(?float $valeur2typesocialrubriquepaiechantier): static
    {
        $this->valeur2typesocialrubriquepaiechantier = $valeur2typesocialrubriquepaiechantier;
        return $this;
    }

    public function getAffichageextranetinterimsocialrubriquepaie(): ?bool
    {
        return $this->affichageextranetinterimsocialrubriquepaie;
    }

    public function setAffichageextranetinterimsocialrubriquepaie(?bool $affichageextranetinterimsocialrubriquepaie): static
    {
        $this->affichageextranetinterimsocialrubriquepaie = $affichageextranetinterimsocialrubriquepaie;
        return $this;
    }

    public function getAffichageinterimsocialrubriquepaie(): ?bool
    {
        return $this->affichageinterimsocialrubriquepaie;
    }

    public function setAffichageinterimsocialrubriquepaie(?bool $affichageinterimsocialrubriquepaie): static
    {
        $this->affichageinterimsocialrubriquepaie = $affichageinterimsocialrubriquepaie;
        return $this;
    }

    public function getActiveinterimsocialrubriquepaie(): ?bool
    {
        return $this->activeinterimsocialrubriquepaie;
    }

    public function setActiveinterimsocialrubriquepaie(?bool $activeinterimsocialrubriquepaie): static
    {
        $this->activeinterimsocialrubriquepaie = $activeinterimsocialrubriquepaie;
        return $this;
    }
}
