<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'PoleActivite')]
class Poleactivite
{
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'IdPoleActivite', type: 'bigint')]
    private int $idpoleactivite;

    #[ORM\Column(name: 'DesignationPoleActivite', type: 'string', nullable: true, length: 50)]
    private ?string $designationpoleactivite = null;

    #[ORM\Column(name: 'IdResponsablePoleActivite', type: 'bigint', nullable: true)]
    private ?int $idresponsablepoleactivite = null;

    #[ORM\Column(name: 'OrdrePoleActivite', type: 'bigint', nullable: true)]
    private ?int $ordrepoleactivite = null;

    #[ORM\Column(name: 'CodeAnalytique', type: 'string', nullable: true, length: 13)]
    private ?string $codeanalytique = null;

    #[ORM\Column(name: 'AdressePoleActivite', type: 'string', nullable: true, length: 500)]
    private ?string $adressepoleactivite = null;

    #[ORM\Column(name: 'CoordonneesGPSPoleActivite', type: 'string', nullable: true, length: 100)]
    private ?string $coordonneesgpspoleactivite = null;

    #[ORM\Column(name: 'IdGeoCodePostaux', type: 'bigint', nullable: true)]
    private ?int $idgeocodepostaux = null;

    #[ORM\Column(name: 'LegendePoleActivite', type: 'string', nullable: true, length: 150)]
    private ?string $legendepoleactivite = null;

    #[ORM\Column(name: 'ActifPoleActivite', type: 'boolean', nullable: true)]
    private ?bool $actifpoleactivite = null;

    #[ORM\Column(name: 'ModeFraisDeplacementPoleActivite', type: 'bigint', nullable: true)]
    private ?int $modefraisdeplacementpoleactivite = null;

    #[ORM\Column(name: 'NomPoleActivite', type: 'string', nullable: true, length: 500)]
    private ?string $nompoleactivite = null;

    #[ORM\Column(name: 'MailPoleActivite', type: 'string', nullable: true, length: 100)]
    private ?string $mailpoleactivite = null;

    #[ORM\Column(name: 'TelPoleActivite', type: 'string', nullable: true, length: 50)]
    private ?string $telpoleactivite = null;

    #[ORM\Column(name: 'LogoPoleActivite', type: 'text', nullable: true)]
    private ?string $logopoleactivite = null;

    public function getIdpoleactivite(): int
    {
        return $this->idpoleactivite;
    }

    public function setIdpoleactivite(int $idpoleactivite): static
    {
        $this->idpoleactivite = $idpoleactivite;
        return $this;
    }

    public function getDesignationpoleactivite(): ?string
    {
        return $this->designationpoleactivite;
    }

    public function setDesignationpoleactivite(?string $designationpoleactivite): static
    {
        $this->designationpoleactivite = $designationpoleactivite;
        return $this;
    }

    public function getIdresponsablepoleactivite(): ?int
    {
        return $this->idresponsablepoleactivite;
    }

    public function setIdresponsablepoleactivite(?int $idresponsablepoleactivite): static
    {
        $this->idresponsablepoleactivite = $idresponsablepoleactivite;
        return $this;
    }

    public function getOrdrepoleactivite(): ?int
    {
        return $this->ordrepoleactivite;
    }

    public function setOrdrepoleactivite(?int $ordrepoleactivite): static
    {
        $this->ordrepoleactivite = $ordrepoleactivite;
        return $this;
    }

    public function getCodeanalytique(): ?string
    {
        return $this->codeanalytique;
    }

    public function setCodeanalytique(?string $codeanalytique): static
    {
        $this->codeanalytique = $codeanalytique;
        return $this;
    }

    public function getAdressepoleactivite(): ?string
    {
        return $this->adressepoleactivite;
    }

    public function setAdressepoleactivite(?string $adressepoleactivite): static
    {
        $this->adressepoleactivite = $adressepoleactivite;
        return $this;
    }

    public function getCoordonneesgpspoleactivite(): ?string
    {
        return $this->coordonneesgpspoleactivite;
    }

    public function setCoordonneesgpspoleactivite(?string $coordonneesgpspoleactivite): static
    {
        $this->coordonneesgpspoleactivite = $coordonneesgpspoleactivite;
        return $this;
    }

    public function getIdgeocodepostaux(): ?int
    {
        return $this->idgeocodepostaux;
    }

    public function setIdgeocodepostaux(?int $idgeocodepostaux): static
    {
        $this->idgeocodepostaux = $idgeocodepostaux;
        return $this;
    }

    public function getLegendepoleactivite(): ?string
    {
        return $this->legendepoleactivite;
    }

    public function setLegendepoleactivite(?string $legendepoleactivite): static
    {
        $this->legendepoleactivite = $legendepoleactivite;
        return $this;
    }

    public function getActifpoleactivite(): ?bool
    {
        return $this->actifpoleactivite;
    }

    public function setActifpoleactivite(?bool $actifpoleactivite): static
    {
        $this->actifpoleactivite = $actifpoleactivite;
        return $this;
    }

    public function getModefraisdeplacementpoleactivite(): ?int
    {
        return $this->modefraisdeplacementpoleactivite;
    }

    public function setModefraisdeplacementpoleactivite(?int $modefraisdeplacementpoleactivite): static
    {
        $this->modefraisdeplacementpoleactivite = $modefraisdeplacementpoleactivite;
        return $this;
    }

    public function getNompoleactivite(): ?string
    {
        return $this->nompoleactivite;
    }

    public function setNompoleactivite(?string $nompoleactivite): static
    {
        $this->nompoleactivite = $nompoleactivite;
        return $this;
    }

    public function getMailpoleactivite(): ?string
    {
        return $this->mailpoleactivite;
    }

    public function setMailpoleactivite(?string $mailpoleactivite): static
    {
        $this->mailpoleactivite = $mailpoleactivite;
        return $this;
    }

    public function getTelpoleactivite(): ?string
    {
        return $this->telpoleactivite;
    }

    public function setTelpoleactivite(?string $telpoleactivite): static
    {
        $this->telpoleactivite = $telpoleactivite;
        return $this;
    }

    public function getLogopoleactivite(): ?string
    {
        return $this->logopoleactivite;
    }

    public function setLogopoleactivite(?string $logopoleactivite): static
    {
        $this->logopoleactivite = $logopoleactivite;
        return $this;
    }
}
