<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'Interim')]
class Interim
{
    #[ORM\Id]
    #[ORM\Column(name: 'IdInterim', type: 'bigint')]
    private int $idinterim;

    #[ORM\Column(name: 'IdStatutJuridique', type: 'integer', nullable: true)]
    private ?int $idstatutjuridique = null;

    #[ORM\Column(name: 'NomInterim', type: 'string', nullable: true, length: 300)]
    private ?string $nominterim = null;

    #[ORM\Column(name: 'PrenomInterim', type: 'string', nullable: true, length: 50)]
    private ?string $prenominterim = null;

    #[ORM\Column(name: 'AdresseInterim', type: 'string', nullable: true, length: 300)]
    private ?string $adresseinterim = null;

    #[ORM\Column(name: 'IdGeoCodePostaux', type: 'integer', nullable: true)]
    private ?int $idgeocodepostaux = null;

    #[ORM\Column(name: 'MailInterim', type: 'string', nullable: true, length: 300)]
    private ?string $mailinterim = null;

    #[ORM\Column(name: 'NoteInterim', type: 'string', nullable: true, length: 3000)]
    private ?string $noteinterim = null;

    #[ORM\Column(name: 'NoteRtfInterim', type: 'string', nullable: true, length: 8000)]
    private ?string $notertfinterim = null;

    #[ORM\Column(name: 'IdSocialRelationSHCategorie', type: 'bigint', nullable: true)]
    private ?int $idsocialrelationshcategorie = null;

    #[ORM\Column(name: 'NiveauInterim', type: 'string', nullable: true, length: 5)]
    private ?string $niveauinterim = null;

    #[ORM\Column(name: 'PositionInterim', type: 'string', nullable: true, length: 5)]
    private ?string $positioninterim = null;

    #[ORM\Column(name: 'CoefficientInterim', type: 'string', nullable: true, length: 5)]
    private ?string $coefficientinterim = null;

    #[ORM\Column(name: 'CompetencePlomberieInterim', type: 'bigint', nullable: true)]
    private ?int $competenceplomberieinterim = null;

    #[ORM\Column(name: 'CompetenceChauffageInterim', type: 'bigint', nullable: true)]
    private ?int $competencechauffageinterim = null;

    #[ORM\Column(name: 'CompetenceVentilationInterim', type: 'bigint', nullable: true)]
    private ?int $competenceventilationinterim = null;

    #[ORM\Column(name: 'CompetenceClimatisationInterim', type: 'bigint', nullable: true)]
    private ?int $competenceclimatisationinterim = null;

    #[ORM\Column(name: 'MarqueurInterim', type: 'integer', nullable: true)]
    private ?int $marqueurinterim = null;

    #[ORM\Column(name: 'IdService', type: 'bigint', nullable: true)]
    private ?int $idservice = null;

    #[ORM\Column(name: 'rowguid', type: 'guid')]
    private string $rowguid;

    #[ORM\Column(name: 'ActifInterim', type: 'boolean', nullable: true)]
    private ?bool $actifinterim = null;

    #[ORM\Column(name: 'IdPoleActivite', type: 'bigint', nullable: true)]
    private ?int $idpoleactivite = null;

    #[ORM\Column(name: 'IdCandidat', type: 'bigint', nullable: true)]
    private ?int $idcandidat = null;

    #[ORM\Column(name: 'IdEquipe', type: 'bigint', nullable: true)]
    private ?int $idequipe = null;

    #[ORM\Column(name: 'SurnomInterim', type: 'string', nullable: true, length: 300)]
    private ?string $surnominterim = null;

    #[ORM\Column(name: 'ProductionInterim', type: 'boolean', nullable: true)]
    private ?bool $productioninterim = null;

    public function getIdinterim(): int
    {
        return $this->idinterim;
    }

    public function setIdinterim(int $idinterim): static
    {
        $this->idinterim = $idinterim;
        return $this;
    }

    public function getIdstatutjuridique(): ?int
    {
        return $this->idstatutjuridique;
    }

    public function setIdstatutjuridique(?int $idstatutjuridique): static
    {
        $this->idstatutjuridique = $idstatutjuridique;
        return $this;
    }

    public function getNominterim(): ?string
    {
        return $this->nominterim;
    }

    public function setNominterim(?string $nominterim): static
    {
        $this->nominterim = $nominterim;
        return $this;
    }

    public function getPrenominterim(): ?string
    {
        return $this->prenominterim;
    }

    public function setPrenominterim(?string $prenominterim): static
    {
        $this->prenominterim = $prenominterim;
        return $this;
    }

    public function getAdresseinterim(): ?string
    {
        return $this->adresseinterim;
    }

    public function setAdresseinterim(?string $adresseinterim): static
    {
        $this->adresseinterim = $adresseinterim;
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

    public function getMailinterim(): ?string
    {
        return $this->mailinterim;
    }

    public function setMailinterim(?string $mailinterim): static
    {
        $this->mailinterim = $mailinterim;
        return $this;
    }

    public function getNoteinterim(): ?string
    {
        return $this->noteinterim;
    }

    public function setNoteinterim(?string $noteinterim): static
    {
        $this->noteinterim = $noteinterim;
        return $this;
    }

    public function getNotertfinterim(): ?string
    {
        return $this->notertfinterim;
    }

    public function setNotertfinterim(?string $notertfinterim): static
    {
        $this->notertfinterim = $notertfinterim;
        return $this;
    }

    public function getIdsocialrelationshcategorie(): ?int
    {
        return $this->idsocialrelationshcategorie;
    }

    public function setIdsocialrelationshcategorie(?int $idsocialrelationshcategorie): static
    {
        $this->idsocialrelationshcategorie = $idsocialrelationshcategorie;
        return $this;
    }

    public function getNiveauinterim(): ?string
    {
        return $this->niveauinterim;
    }

    public function setNiveauinterim(?string $niveauinterim): static
    {
        $this->niveauinterim = $niveauinterim;
        return $this;
    }

    public function getPositioninterim(): ?string
    {
        return $this->positioninterim;
    }

    public function setPositioninterim(?string $positioninterim): static
    {
        $this->positioninterim = $positioninterim;
        return $this;
    }

    public function getCoefficientinterim(): ?string
    {
        return $this->coefficientinterim;
    }

    public function setCoefficientinterim(?string $coefficientinterim): static
    {
        $this->coefficientinterim = $coefficientinterim;
        return $this;
    }

    public function getCompetenceplomberieinterim(): ?int
    {
        return $this->competenceplomberieinterim;
    }

    public function setCompetenceplomberieinterim(?int $competenceplomberieinterim): static
    {
        $this->competenceplomberieinterim = $competenceplomberieinterim;
        return $this;
    }

    public function getCompetencechauffageinterim(): ?int
    {
        return $this->competencechauffageinterim;
    }

    public function setCompetencechauffageinterim(?int $competencechauffageinterim): static
    {
        $this->competencechauffageinterim = $competencechauffageinterim;
        return $this;
    }

    public function getCompetenceventilationinterim(): ?int
    {
        return $this->competenceventilationinterim;
    }

    public function setCompetenceventilationinterim(?int $competenceventilationinterim): static
    {
        $this->competenceventilationinterim = $competenceventilationinterim;
        return $this;
    }

    public function getCompetenceclimatisationinterim(): ?int
    {
        return $this->competenceclimatisationinterim;
    }

    public function setCompetenceclimatisationinterim(?int $competenceclimatisationinterim): static
    {
        $this->competenceclimatisationinterim = $competenceclimatisationinterim;
        return $this;
    }

    public function getMarqueurinterim(): ?int
    {
        return $this->marqueurinterim;
    }

    public function setMarqueurinterim(?int $marqueurinterim): static
    {
        $this->marqueurinterim = $marqueurinterim;
        return $this;
    }

    public function getIdservice(): ?int
    {
        return $this->idservice;
    }

    public function setIdservice(?int $idservice): static
    {
        $this->idservice = $idservice;
        return $this;
    }

    public function getRowguid(): string
    {
        return $this->rowguid;
    }

    public function setRowguid(string $rowguid): static
    {
        $this->rowguid = $rowguid;
        return $this;
    }

    public function getActifinterim(): ?bool
    {
        return $this->actifinterim;
    }

    public function setActifinterim(?bool $actifinterim): static
    {
        $this->actifinterim = $actifinterim;
        return $this;
    }

    public function getIdpoleactivite(): ?int
    {
        return $this->idpoleactivite;
    }

    public function setIdpoleactivite(?int $idpoleactivite): static
    {
        $this->idpoleactivite = $idpoleactivite;
        return $this;
    }

    public function getIdcandidat(): ?int
    {
        return $this->idcandidat;
    }

    public function setIdcandidat(?int $idcandidat): static
    {
        $this->idcandidat = $idcandidat;
        return $this;
    }

    public function getIdequipe(): ?int
    {
        return $this->idequipe;
    }

    public function setIdequipe(?int $idequipe): static
    {
        $this->idequipe = $idequipe;
        return $this;
    }

    public function getSurnominterim(): ?string
    {
        return $this->surnominterim;
    }

    public function setSurnominterim(?string $surnominterim): static
    {
        $this->surnominterim = $surnominterim;
        return $this;
    }

    public function getProductioninterim(): ?bool
    {
        return $this->productioninterim;
    }

    public function setProductioninterim(?bool $productioninterim): static
    {
        $this->productioninterim = $productioninterim;
        return $this;
    }
}
