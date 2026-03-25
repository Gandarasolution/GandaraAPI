<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'Salarie')]
class Salarie
{
    #[ORM\Id]
    #[ORM\Column(name: 'IdSalarie', type: 'bigint')]
    private int $idsalarie;

    #[ORM\Column(name: 'PassMotSalarie', type: 'string', nullable: true, length: 10)]
    private ?string $passmotsalarie = null;

    #[ORM\Column(name: 'IdGroupe', type: 'bigint', nullable: true)]
    private ?int $idgroupe = null;

    #[ORM\Column(name: 'PortableEntrepriseSalarie', type: 'string', nullable: true, length: 20)]
    private ?string $portableentreprisesalarie = null;

    #[ORM\Column(name: 'Actif', type: 'boolean', nullable: true)]
    private ?bool $actif = null;

    #[ORM\Column(name: 'IdService', type: 'bigint', nullable: true)]
    private ?int $idservice = null;

    #[ORM\Column(name: 'IdStatutJuridique', type: 'bigint', nullable: true)]
    private ?int $idstatutjuridique = null;

    #[ORM\Column(name: 'NomSalarie', type: 'string', nullable: true, length: 300)]
    private ?string $nomsalarie = null;

    #[ORM\Column(name: 'PrenomSalarie', type: 'string', nullable: true, length: 50)]
    private ?string $prenomsalarie = null;

    #[ORM\Column(name: 'AdresseSalarie', type: 'string', nullable: true, length: 300)]
    private ?string $adressesalarie = null;

    #[ORM\Column(name: 'IdGeoCodePostaux', type: 'bigint', nullable: true)]
    private ?int $idgeocodepostaux = null;

    #[ORM\Column(name: 'MailSalarie', type: 'string', nullable: true, length: 300)]
    private ?string $mailsalarie = null;

    #[ORM\Column(name: 'NoteSalarie', type: 'string', nullable: true, length: 3000)]
    private ?string $notesalarie = null;

    #[ORM\Column(name: 'NoteRtfSalarie', type: 'string', nullable: true, length: 3000)]
    private ?string $notertfsalarie = null;

    #[ORM\Column(name: 'CompetencePlomberieSalarie', type: 'bigint', nullable: true)]
    private ?int $competenceplomberiesalarie = null;

    #[ORM\Column(name: 'CompetenceChauffageSalarie', type: 'bigint', nullable: true)]
    private ?int $competencechauffagesalarie = null;

    #[ORM\Column(name: 'CompetenceVentilationSalarie', type: 'bigint', nullable: true)]
    private ?int $competenceventilationsalarie = null;

    #[ORM\Column(name: 'CompetenceClimatisationSalarie', type: 'bigint', nullable: true)]
    private ?int $competenceclimatisationsalarie = null;

    #[ORM\Column(name: 'MarqueurSalarie', type: 'integer', nullable: true)]
    private ?int $marqueursalarie = null;

    #[ORM\Column(name: 'NaissanceDateSalarie', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $naissancedatesalarie = null;

    #[ORM\Column(name: 'NaissanceIdGeoCodePostaux', type: 'integer', nullable: true)]
    private ?int $naissanceidgeocodepostaux = null;

    #[ORM\Column(name: 'NumeroSecuriteSocialSalarie', type: 'string', nullable: true, length: 50)]
    private ?string $numerosecuritesocialsalarie = null;

    #[ORM\Column(name: 'SexeSalarie', type: 'integer', nullable: true)]
    private ?int $sexesalarie = null;

    #[ORM\Column(name: 'SituationFamilialSalarie', type: 'integer', nullable: true)]
    private ?int $situationfamilialsalarie = null;

    #[ORM\Column(name: 'CPAM', type: 'integer', nullable: true)]
    private ?int $cpam = null;

    #[ORM\Column(name: 'rowguid', type: 'guid')]
    private string $rowguid;

    #[ORM\Column(name: 'CodeFacturation', type: 'string', nullable: true, length: 4)]
    private ?string $codefacturation = null;

    #[ORM\Column(name: 'IdTypeCategorieSalarie', type: 'bigint', nullable: true)]
    private ?int $idtypecategoriesalarie = null;

    #[ORM\ManyToOne(targetEntity: Poleactivite::class)]
    #[ORM\JoinColumn(name: 'IdPoleactivite', referencedColumnName: 'IdPoleActivite', nullable: true)]
    private ?Poleactivite $idpoleactivite = null;

    #[ORM\Column(name: 'IdSalarieIndemniteRepas', type: 'bigint', nullable: true)]
    private ?int $idsalarieindemniterepas = null;

    #[ORM\Column(name: 'AbbattementDixPourCent', type: 'boolean', nullable: true)]
    private ?bool $abbattementdixpourcent = null;

    #[ORM\Column(name: 'SoumisAAstreinte', type: 'boolean', nullable: true)]
    private ?bool $soumisaastreinte = null;

    #[ORM\Column(name: 'CoordonneesGPSSalarie', type: 'string', nullable: true, length: 100)]
    private ?string $coordonneesgpssalarie = null;

    #[ORM\Column(name: 'IdCandidat', type: 'bigint', nullable: true)]
    private ?int $idcandidat = null;

    #[ORM\Column(name: 'IdInterim', type: 'bigint', nullable: true)]
    private ?int $idinterim = null;

    #[ORM\Column(name: 'IdAgence', type: 'bigint', nullable: true)]
    private ?int $idagence = null;

    #[ORM\ManyToOne(targetEntity: Equipe::class)]
    #[ORM\JoinColumn(name: 'Idequipe', referencedColumnName: 'IdEquipe', nullable: true)]
    private ?Equipe $idequipe = null;

    #[ORM\Column(name: 'SurnomSalarie', type: 'string', nullable: true, length: 300)]
    private ?string $surnomsalarie = null;

    #[ORM\Column(name: 'NationaliteSalarie', type: 'string', nullable: true, length: 50)]
    private ?string $nationalitesalarie = null;

    #[ORM\Column(name: 'NbEnfantSalarie', type: 'bigint', nullable: true)]
    private ?int $nbenfantsalarie = null;

    #[ORM\Column(name: 'NumCarteBTPSalarie', type: 'string', nullable: true, length: 50)]
    private ?string $numcartebtpsalarie = null;

    #[ORM\Column(name: 'MutuelEntreprise', type: 'boolean', nullable: true)]
    private ?bool $mutuelentreprise = null;

    #[ORM\Column(name: 'NumAdherentMutuelleSalarie', type: 'string', nullable: true, length: 100)]
    private ?string $numadherentmutuellesalarie = null;

    #[ORM\Column(name: 'MutuelleSalarie', type: 'string', nullable: true, length: 100)]
    private ?string $mutuellesalarie = null;

    #[ORM\Column(name: 'EPIPointureSalarie', type: 'string', nullable: true, length: 10)]
    private ?string $epipointuresalarie = null;

    #[ORM\Column(name: 'EPITailleHautSalarie', type: 'string', nullable: true, length: 10)]
    private ?string $epitaillehautsalarie = null;

    #[ORM\Column(name: 'EPITailleBasSalarie', type: 'string', nullable: true, length: 10)]
    private ?string $epitaillebassalarie = null;

    #[ORM\Column(name: 'FormuleMutuelleSalarie', type: 'string', nullable: true, length: 100)]
    private ?string $formulemutuellesalarie = null;

    #[ORM\Column(name: 'ProductionSalarie', type: 'boolean', nullable: true)]
    private ?bool $productionsalarie = null;

    public function getIdsalarie(): int
    {
        return $this->idsalarie;
    }

    public function setIdsalarie(int $idsalarie): static
    {
        $this->idsalarie = $idsalarie;
        return $this;
    }

    public function getPassmotsalarie(): ?string
    {
        return $this->passmotsalarie;
    }

    public function setPassmotsalarie(?string $passmotsalarie): static
    {
        $this->passmotsalarie = $passmotsalarie;
        return $this;
    }

    public function getIdgroupe(): ?Groupe
    {
        return $this->idgroupe;
    }

    public function setIdgroupe(?Groupe $idgroupe): static
    {
        $this->idgroupe = $idgroupe;
        return $this;
    }

    public function getPortableentreprisesalarie(): ?string
    {
        return $this->portableentreprisesalarie;
    }

    public function setPortableentreprisesalarie(?string $portableentreprisesalarie): static
    {
        $this->portableentreprisesalarie = $portableentreprisesalarie;
        return $this;
    }

    public function getActif(): ?bool
    {
        return $this->actif;
    }

    public function setActif(?bool $actif): static
    {
        $this->actif = $actif;
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

    public function getIdstatutjuridique(): ?int
    {
        return $this->idstatutjuridique;
    }

    public function setIdstatutjuridique(?int $idstatutjuridique): static
    {
        $this->idstatutjuridique = $idstatutjuridique;
        return $this;
    }

    public function getNomsalarie(): ?string
    {
        return $this->nomsalarie;
    }

    public function setNomsalarie(?string $nomsalarie): static
    {
        $this->nomsalarie = $nomsalarie;
        return $this;
    }

    public function getPrenomsalarie(): ?string
    {
        return $this->prenomsalarie;
    }

    public function setPrenomsalarie(?string $prenomsalarie): static
    {
        $this->prenomsalarie = $prenomsalarie;
        return $this;
    }

    public function getAdressesalarie(): ?string
    {
        return $this->adressesalarie;
    }

    public function setAdressesalarie(?string $adressesalarie): static
    {
        $this->adressesalarie = $adressesalarie;
        return $this;
    }

    public function getIdgeocodepostaux(): ?Geocodepostaux
    {
        return $this->idgeocodepostaux;
    }

    public function setIdgeocodepostaux(?Geocodepostaux $idgeocodepostaux): static
    {
        $this->idgeocodepostaux = $idgeocodepostaux;
        return $this;
    }

    public function getMailsalarie(): ?string
    {
        return $this->mailsalarie;
    }

    public function setMailsalarie(?string $mailsalarie): static
    {
        $this->mailsalarie = $mailsalarie;
        return $this;
    }

    public function getNotesalarie(): ?string
    {
        return $this->notesalarie;
    }

    public function setNotesalarie(?string $notesalarie): static
    {
        $this->notesalarie = $notesalarie;
        return $this;
    }

    public function getNotertfsalarie(): ?string
    {
        return $this->notertfsalarie;
    }

    public function setNotertfsalarie(?string $notertfsalarie): static
    {
        $this->notertfsalarie = $notertfsalarie;
        return $this;
    }

    public function getCompetenceplomberiesalarie(): ?int
    {
        return $this->competenceplomberiesalarie;
    }

    public function setCompetenceplomberiesalarie(?int $competenceplomberiesalarie): static
    {
        $this->competenceplomberiesalarie = $competenceplomberiesalarie;
        return $this;
    }

    public function getCompetencechauffagesalarie(): ?int
    {
        return $this->competencechauffagesalarie;
    }

    public function setCompetencechauffagesalarie(?int $competencechauffagesalarie): static
    {
        $this->competencechauffagesalarie = $competencechauffagesalarie;
        return $this;
    }

    public function getCompetenceventilationsalarie(): ?int
    {
        return $this->competenceventilationsalarie;
    }

    public function setCompetenceventilationsalarie(?int $competenceventilationsalarie): static
    {
        $this->competenceventilationsalarie = $competenceventilationsalarie;
        return $this;
    }

    public function getCompetenceclimatisationsalarie(): ?int
    {
        return $this->competenceclimatisationsalarie;
    }

    public function setCompetenceclimatisationsalarie(?int $competenceclimatisationsalarie): static
    {
        $this->competenceclimatisationsalarie = $competenceclimatisationsalarie;
        return $this;
    }

    public function getMarqueursalarie(): ?int
    {
        return $this->marqueursalarie;
    }

    public function setMarqueursalarie(?int $marqueursalarie): static
    {
        $this->marqueursalarie = $marqueursalarie;
        return $this;
    }

    public function getNaissancedatesalarie(): ?\DateTimeInterface
    {
        return $this->naissancedatesalarie;
    }

    public function setNaissancedatesalarie(?\DateTimeInterface $naissancedatesalarie): static
    {
        $this->naissancedatesalarie = $naissancedatesalarie;
        return $this;
    }

    public function getNaissanceidgeocodepostaux(): ?int
    {
        return $this->naissanceidgeocodepostaux;
    }

    public function setNaissanceidgeocodepostaux(?int $naissanceidgeocodepostaux): static
    {
        $this->naissanceidgeocodepostaux = $naissanceidgeocodepostaux;
        return $this;
    }

    public function getNumerosecuritesocialsalarie(): ?string
    {
        return $this->numerosecuritesocialsalarie;
    }

    public function setNumerosecuritesocialsalarie(?string $numerosecuritesocialsalarie): static
    {
        $this->numerosecuritesocialsalarie = $numerosecuritesocialsalarie;
        return $this;
    }

    public function getSexesalarie(): ?int
    {
        return $this->sexesalarie;
    }

    public function setSexesalarie(?int $sexesalarie): static
    {
        $this->sexesalarie = $sexesalarie;
        return $this;
    }

    public function getSituationfamilialsalarie(): ?int
    {
        return $this->situationfamilialsalarie;
    }

    public function setSituationfamilialsalarie(?int $situationfamilialsalarie): static
    {
        $this->situationfamilialsalarie = $situationfamilialsalarie;
        return $this;
    }

    public function getCpam(): ?int
    {
        return $this->cpam;
    }

    public function setCpam(?int $cpam): static
    {
        $this->cpam = $cpam;
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

    public function getCodefacturation(): ?string
    {
        return $this->codefacturation;
    }

    public function setCodefacturation(?string $codefacturation): static
    {
        $this->codefacturation = $codefacturation;
        return $this;
    }

    public function getIdtypecategoriesalarie(): ?int
    {
        return $this->idtypecategoriesalarie;
    }

    public function setIdtypecategoriesalarie(?int $idtypecategoriesalarie): static
    {
        $this->idtypecategoriesalarie = $idtypecategoriesalarie;
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

    public function getIdsalarieindemniterepas(): ?int
    {
        return $this->idsalarieindemniterepas;
    }

    public function setIdsalarieindemniterepas(?int $idsalarieindemniterepas): static
    {
        $this->idsalarieindemniterepas = $idsalarieindemniterepas;
        return $this;
    }

    public function getAbbattementdixpourcent(): ?bool
    {
        return $this->abbattementdixpourcent;
    }

    public function setAbbattementdixpourcent(?bool $abbattementdixpourcent): static
    {
        $this->abbattementdixpourcent = $abbattementdixpourcent;
        return $this;
    }

    public function getSoumisaastreinte(): ?bool
    {
        return $this->soumisaastreinte;
    }

    public function setSoumisaastreinte(?bool $soumisaastreinte): static
    {
        $this->soumisaastreinte = $soumisaastreinte;
        return $this;
    }

    public function getCoordonneesgpssalarie(): ?string
    {
        return $this->coordonneesgpssalarie;
    }

    public function setCoordonneesgpssalarie(?string $coordonneesgpssalarie): static
    {
        $this->coordonneesgpssalarie = $coordonneesgpssalarie;
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

    public function getIdinterim(): ?int
    {
        return $this->idinterim;
    }

    public function setIdinterim(?int $idinterim): static
    {
        $this->idinterim = $idinterim;
        return $this;
    }

    public function getIdagence(): ?int
    {
        return $this->idagence;
    }

    public function setIdagence(?int $idagence): static
    {
        $this->idagence = $idagence;
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

    public function getSurnomsalarie(): ?string
    {
        return $this->surnomsalarie;
    }

    public function setSurnomsalarie(?string $surnomsalarie): static
    {
        $this->surnomsalarie = $surnomsalarie;
        return $this;
    }

    public function getNationalitesalarie(): ?string
    {
        return $this->nationalitesalarie;
    }

    public function setNationalitesalarie(?string $nationalitesalarie): static
    {
        $this->nationalitesalarie = $nationalitesalarie;
        return $this;
    }

    public function getNbenfantsalarie(): ?int
    {
        return $this->nbenfantsalarie;
    }

    public function setNbenfantsalarie(?int $nbenfantsalarie): static
    {
        $this->nbenfantsalarie = $nbenfantsalarie;
        return $this;
    }

    public function getNumcartebtpsalarie(): ?string
    {
        return $this->numcartebtpsalarie;
    }

    public function setNumcartebtpsalarie(?string $numcartebtpsalarie): static
    {
        $this->numcartebtpsalarie = $numcartebtpsalarie;
        return $this;
    }

    public function getMutuelentreprise(): ?bool
    {
        return $this->mutuelentreprise;
    }

    public function setMutuelentreprise(?bool $mutuelentreprise): static
    {
        $this->mutuelentreprise = $mutuelentreprise;
        return $this;
    }

    public function getNumadherentmutuellesalarie(): ?string
    {
        return $this->numadherentmutuellesalarie;
    }

    public function setNumadherentmutuellesalarie(?string $numadherentmutuellesalarie): static
    {
        $this->numadherentmutuellesalarie = $numadherentmutuellesalarie;
        return $this;
    }

    public function getMutuellesalarie(): ?string
    {
        return $this->mutuellesalarie;
    }

    public function setMutuellesalarie(?string $mutuellesalarie): static
    {
        $this->mutuellesalarie = $mutuellesalarie;
        return $this;
    }

    public function getEpipointuresalarie(): ?string
    {
        return $this->epipointuresalarie;
    }

    public function setEpipointuresalarie(?string $epipointuresalarie): static
    {
        $this->epipointuresalarie = $epipointuresalarie;
        return $this;
    }

    public function getEpitaillehautsalarie(): ?string
    {
        return $this->epitaillehautsalarie;
    }

    public function setEpitaillehautsalarie(?string $epitaillehautsalarie): static
    {
        $this->epitaillehautsalarie = $epitaillehautsalarie;
        return $this;
    }

    public function getEpitaillebassalarie(): ?string
    {
        return $this->epitaillebassalarie;
    }

    public function setEpitaillebassalarie(?string $epitaillebassalarie): static
    {
        $this->epitaillebassalarie = $epitaillebassalarie;
        return $this;
    }

    public function getFormulemutuellesalarie(): ?string
    {
        return $this->formulemutuellesalarie;
    }

    public function setFormulemutuellesalarie(?string $formulemutuellesalarie): static
    {
        $this->formulemutuellesalarie = $formulemutuellesalarie;
        return $this;
    }

    public function getProductionsalarie(): ?bool
    {
        return $this->productionsalarie;
    }

    public function setProductionsalarie(?bool $productionsalarie): static
    {
        $this->productionsalarie = $productionsalarie;
        return $this;
    }
}
