<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'Projet')]
class Projet
{
    #[ORM\Id]
    #[ORM\Column(name: 'IdProjet', type: 'bigint')]
    private int $idprojet;

    #[ORM\Column(name: 'SujetProjet', type: 'string', nullable: true, length: 3000)]
    private ?string $sujetprojet = null;

    #[ORM\Column(name: 'SujetRTFProjet', type: 'string', nullable: true, length: 50)]
    private ?string $sujetrtfprojet = null;

    #[ORM\Column(name: 'NoteProjet', type: 'string', nullable: true, length: 3000)]
    private ?string $noteprojet = null;

    #[ORM\Column(name: 'NoteRtfProjet', type: 'string', nullable: true, length: 50)]
    private ?string $notertfprojet = null;

    #[ORM\Column(name: 'IdEtat', type: 'integer', nullable: true)]
    private ?int $idetat = null;

    #[ORM\Column(name: 'IdClientSiteRelation', type: 'bigint', nullable: true)]
    private ?int $idclientsiterelation = null;

    #[ORM\Column(name: 'IdAffaire', type: 'integer', nullable: true)]
    private ?int $idaffaire = null;

    #[ORM\Column(name: 'IdRespCom', type: 'bigint', nullable: true)]
    private ?int $idrespcom = null;

    #[ORM\Column(name: 'DemandeClientProjet', type: 'string', nullable: true, length: 3000)]
    private ?string $demandeclientprojet = null;

    #[ORM\Column(name: 'BibliothequeProjet', type: 'string', nullable: true, length: 10)]
    private ?string $bibliothequeprojet = null;

    #[ORM\Column(name: 'TypeProjet', type: 'integer', nullable: true)]
    private ?int $typeprojet = null;

    #[ORM\Column(name: 'IdentificationProjet', type: 'string', nullable: true, length: 30)]
    private ?string $identificationprojet = null;

    #[ORM\Column(name: 'MotifAnnulationProjet', type: 'string', nullable: true, length: 100)]
    private ?string $motifannulationprojet = null;

    #[ORM\Column(name: 'DateAnnulationProjet', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $dateannulationprojet = null;

    #[ORM\Column(name: 'IdSalarieAnnulationProjet', type: 'bigint', nullable: true)]
    private ?int $idsalarieannulationprojet = null;

    #[ORM\Column(name: 'IdChargeAffaire', type: 'bigint', nullable: true)]
    private ?int $idchargeaffaire = null;

    #[ORM\Column(name: 'DecennaleProjet', type: 'boolean', nullable: true)]
    private ?bool $decennaleprojet = null;

    #[ORM\Column(name: 'ITProjet', type: 'decimal', precision: 18, scale: 4, nullable: true)]
    private ?float $itprojet = null;

    #[ORM\Column(name: 'PrimeProjet', type: 'smallint', nullable: true)]
    private ?int $primeprojet = null;

    #[ORM\Column(name: 'CodeBatigest', type: 'string', nullable: true, length: 30)]
    private ?string $codebatigest = null;

    #[ORM\Column(name: 'ReceptionProjet', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $receptionprojet = null;

    #[ORM\Column(name: 'LeveReserveProjet', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $levereserveprojet = null;

    #[ORM\Column(name: 'ValidationDGDProjet', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $validationdgdprojet = null;

    #[ORM\Column(name: 'PasReceptionProjet', type: 'boolean', nullable: true)]
    private ?bool $pasreceptionprojet = null;

    #[ORM\Column(name: 'PasLeveReserveProjet', type: 'boolean', nullable: true)]
    private ?bool $paslevereserveprojet = null;

    #[ORM\Column(name: 'PasValidationDGDProjet', type: 'boolean', nullable: true)]
    private ?bool $pasvalidationdgdprojet = null;

    #[ORM\Column(name: 'IndicateurCommercialProjet', type: 'integer', nullable: true)]
    private ?int $indicateurcommercialprojet = null;

    #[ORM\Column(name: 'IdChefChantierProjet', type: 'bigint', nullable: true)]
    private ?int $idchefchantierprojet = null;

    #[ORM\Column(name: 'IdReferentEtudeProjet', type: 'bigint', nullable: true)]
    private ?int $idreferentetudeprojet = null;

    #[ORM\Column(name: 'IdGestionnaireAffaireProjet', type: 'bigint', nullable: true)]
    private ?int $idgestionnaireaffaireprojet = null;

    #[ORM\Column(name: 'DateAcceptationProjet', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $dateacceptationprojet = null;

    #[ORM\Column(name: 'DateSignatureMarche', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $datesignaturemarche = null;

    #[ORM\Column(name: 'DateOrdreService', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $dateordreservice = null;

    #[ORM\Column(name: 'DateAvenantProlongation', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $dateavenantprolongation = null;

    #[ORM\Column(name: 'NbMoisDelaiContractuel', precision: 10, scale: 2, type: 'decimal', nullable: true)]
    private ?float $nbmoisdelaicontractuel = null;

    #[ORM\Column(name: 'NbMoisPeriodePreparation', precision: 10, scale: 2, type: 'decimal', nullable: true)]
    private ?float $nbmoisperiodepreparation = null;

    #[ORM\Column(name: 'IdTypeProjet', type: 'bigint', nullable: true)]
    private ?int $idtypeprojet = null;

    #[ORM\Column(name: 'DateLimiteRemiseProjet', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $datelimiteremiseprojet = null;

    #[ORM\Column(name: 'DateEnvoiRemiseProjet', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $dateenvoiremiseprojet = null;

    #[ORM\Column(name: 'IdTypeEnvoiRemiseProjet', type: 'bigint', nullable: true)]
    private ?int $idtypeenvoiremiseprojet = null;

    #[ORM\Column(name: 'EmetteurEnvoiRemiseProjet', type: 'bigint', nullable: true)]
    private ?int $emetteurenvoiremiseprojet = null;

    #[ORM\Column(name: 'CheminRelatifProjet', type: 'string', nullable: true, length: 500)]
    private ?string $cheminrelatifprojet = null;

    #[ORM\Column(name: 'IdPereProjet', type: 'bigint', nullable: true)]
    private ?int $idpereprojet = null;

    #[ORM\Column(name: 'InterneProjet', type: 'boolean', nullable: true)]
    private ?bool $interneprojet = null;

    #[ORM\Column(name: 'TagPlanifiable', type: 'boolean', nullable: true)]
    private ?bool $tagplanifiable = null;

    #[ORM\Column(name: 'IdPoleActivite', type: 'bigint', nullable: true)]
    private ?int $idpoleactivite = null;

    #[ORM\Column(name: 'DateVisite', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $datevisite = null;

    #[ORM\Column(name: 'IdVisiteur', type: 'bigint', nullable: true)]
    private ?int $idvisiteur = null;

    #[ORM\Column(name: 'TagProjet', type: 'string', nullable: true, length: 50)]
    private ?string $tagprojet = null;

    #[ORM\Column(name: 'EstProjetConsolide', type: 'boolean', nullable: true)]
    private ?bool $estprojetconsolide = null;

    #[ORM\Column(name: 'ModePropositionFacturation', type: 'boolean', nullable: true)]
    private ?bool $modepropositionfacturation = null;

    #[ORM\Column(name: 'DateReglementaireOuverture', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $datereglementaireouverture = null;

    #[ORM\Column(name: 'IdBanqueCaisse', type: 'bigint', nullable: true)]
    private ?int $idbanquecaisse = null;

    public function getIdprojet(): int
    {
        return $this->idprojet;
    }

    public function setIdprojet(int $idprojet): static
    {
        $this->idprojet = $idprojet;
        return $this;
    }

    public function getSujetprojet(): ?string
    {
        return $this->sujetprojet;
    }

    public function setSujetprojet(?string $sujetprojet): static
    {
        $this->sujetprojet = $sujetprojet;
        return $this;
    }

    public function getSujetrtfprojet(): ?string
    {
        return $this->sujetrtfprojet;
    }

    public function setSujetrtfprojet(?string $sujetrtfprojet): static
    {
        $this->sujetrtfprojet = $sujetrtfprojet;
        return $this;
    }

    public function getNoteprojet(): ?string
    {
        return $this->noteprojet;
    }

    public function setNoteprojet(?string $noteprojet): static
    {
        $this->noteprojet = $noteprojet;
        return $this;
    }

    public function getNotertfprojet(): ?string
    {
        return $this->notertfprojet;
    }

    public function setNotertfprojet(?string $notertfprojet): static
    {
        $this->notertfprojet = $notertfprojet;
        return $this;
    }

    public function getIdetat(): ?int
    {
        return $this->idetat;
    }

    public function setIdetat(?int $idetat): static
    {
        $this->idetat = $idetat;
        return $this;
    }

    public function getIdclientsiterelation(): ?int
    {
        return $this->idclientsiterelation;
    }

    public function setIdclientsiterelation(?int $idclientsiterelation): static
    {
        $this->idclientsiterelation = $idclientsiterelation;
        return $this;
    }

    public function getIdaffaire(): ?int
    {
        return $this->idaffaire;
    }

    public function setIdaffaire(?int $idaffaire): static
    {
        $this->idaffaire = $idaffaire;
        return $this;
    }

    public function getIdrespcom(): ?int
    {
        return $this->idrespcom;
    }

    public function setIdrespcom(?int $idrespcom): static
    {
        $this->idrespcom = $idrespcom;
        return $this;
    }

    public function getDemandeclientprojet(): ?string
    {
        return $this->demandeclientprojet;
    }

    public function setDemandeclientprojet(?string $demandeclientprojet): static
    {
        $this->demandeclientprojet = $demandeclientprojet;
        return $this;
    }

    public function getBibliothequeprojet(): ?string
    {
        return $this->bibliothequeprojet;
    }

    public function setBibliothequeprojet(?string $bibliothequeprojet): static
    {
        $this->bibliothequeprojet = $bibliothequeprojet;
        return $this;
    }

    public function getTypeprojet(): ?int
    {
        return $this->typeprojet;
    }

    public function setTypeprojet(?int $typeprojet): static
    {
        $this->typeprojet = $typeprojet;
        return $this;
    }

    public function getIdentificationprojet(): ?string
    {
        return $this->identificationprojet;
    }

    public function setIdentificationprojet(?string $identificationprojet): static
    {
        $this->identificationprojet = $identificationprojet;
        return $this;
    }

    public function getMotifannulationprojet(): ?string
    {
        return $this->motifannulationprojet;
    }

    public function setMotifannulationprojet(?string $motifannulationprojet): static
    {
        $this->motifannulationprojet = $motifannulationprojet;
        return $this;
    }

    public function getDateannulationprojet(): ?\DateTimeInterface
    {
        return $this->dateannulationprojet;
    }

    public function setDateannulationprojet(?\DateTimeInterface $dateannulationprojet): static
    {
        $this->dateannulationprojet = $dateannulationprojet;
        return $this;
    }

    public function getIdsalarieannulationprojet(): ?int
    {
        return $this->idsalarieannulationprojet;
    }

    public function setIdsalarieannulationprojet(?int $idsalarieannulationprojet): static
    {
        $this->idsalarieannulationprojet = $idsalarieannulationprojet;
        return $this;
    }

    public function getIdchargeaffaire(): ?int
    {
        return $this->idchargeaffaire;
    }

    public function setIdchargeaffaire(?int $idchargeaffaire): static
    {
        $this->idchargeaffaire = $idchargeaffaire;
        return $this;
    }

    public function getDecennaleprojet(): ?bool
    {
        return $this->decennaleprojet;
    }

    public function setDecennaleprojet(?bool $decennaleprojet): static
    {
        $this->decennaleprojet = $decennaleprojet;
        return $this;
    }

    public function getItprojet(): ?float
    {
        return $this->itprojet;
    }

    public function setItprojet(?float $itprojet): static
    {
        $this->itprojet = $itprojet;
        return $this;
    }

    public function getPrimeprojet(): ?int
    {
        return $this->primeprojet;
    }

    public function setPrimeprojet(?int $primeprojet): static
    {
        $this->primeprojet = $primeprojet;
        return $this;
    }

    public function getCodebatigest(): ?string
    {
        return $this->codebatigest;
    }

    public function setCodebatigest(?string $codebatigest): static
    {
        $this->codebatigest = $codebatigest;
        return $this;
    }

    public function getReceptionprojet(): ?\DateTimeInterface
    {
        return $this->receptionprojet;
    }

    public function setReceptionprojet(?\DateTimeInterface $receptionprojet): static
    {
        $this->receptionprojet = $receptionprojet;
        return $this;
    }

    public function getLevereserveprojet(): ?\DateTimeInterface
    {
        return $this->levereserveprojet;
    }

    public function setLevereserveprojet(?\DateTimeInterface $levereserveprojet): static
    {
        $this->levereserveprojet = $levereserveprojet;
        return $this;
    }

    public function getValidationdgdprojet(): ?\DateTimeInterface
    {
        return $this->validationdgdprojet;
    }

    public function setValidationdgdprojet(?\DateTimeInterface $validationdgdprojet): static
    {
        $this->validationdgdprojet = $validationdgdprojet;
        return $this;
    }

    public function getPasreceptionprojet(): ?bool
    {
        return $this->pasreceptionprojet;
    }

    public function setPasreceptionprojet(?bool $pasreceptionprojet): static
    {
        $this->pasreceptionprojet = $pasreceptionprojet;
        return $this;
    }

    public function getPaslevereserveprojet(): ?bool
    {
        return $this->paslevereserveprojet;
    }

    public function setPaslevereserveprojet(?bool $paslevereserveprojet): static
    {
        $this->paslevereserveprojet = $paslevereserveprojet;
        return $this;
    }

    public function getPasvalidationdgdprojet(): ?bool
    {
        return $this->pasvalidationdgdprojet;
    }

    public function setPasvalidationdgdprojet(?bool $pasvalidationdgdprojet): static
    {
        $this->pasvalidationdgdprojet = $pasvalidationdgdprojet;
        return $this;
    }

    public function getIndicateurcommercialprojet(): ?int
    {
        return $this->indicateurcommercialprojet;
    }

    public function setIndicateurcommercialprojet(?int $indicateurcommercialprojet): static
    {
        $this->indicateurcommercialprojet = $indicateurcommercialprojet;
        return $this;
    }

    public function getIdchefchantierprojet(): ?int
    {
        return $this->idchefchantierprojet;
    }

    public function setIdchefchantierprojet(?int $idchefchantierprojet): static
    {
        $this->idchefchantierprojet = $idchefchantierprojet;
        return $this;
    }

    public function getIdreferentetudeprojet(): ?int
    {
        return $this->idreferentetudeprojet;
    }

    public function setIdreferentetudeprojet(?int $idreferentetudeprojet): static
    {
        $this->idreferentetudeprojet = $idreferentetudeprojet;
        return $this;
    }

    public function getIdgestionnaireaffaireprojet(): ?int
    {
        return $this->idgestionnaireaffaireprojet;
    }

    public function setIdgestionnaireaffaireprojet(?int $idgestionnaireaffaireprojet): static
    {
        $this->idgestionnaireaffaireprojet = $idgestionnaireaffaireprojet;
        return $this;
    }

    public function getDateacceptationprojet(): ?\DateTimeInterface
    {
        return $this->dateacceptationprojet;
    }

    public function setDateacceptationprojet(?\DateTimeInterface $dateacceptationprojet): static
    {
        $this->dateacceptationprojet = $dateacceptationprojet;
        return $this;
    }

    public function getDatesignaturemarche(): ?\DateTimeInterface
    {
        return $this->datesignaturemarche;
    }

    public function setDatesignaturemarche(?\DateTimeInterface $datesignaturemarche): static
    {
        $this->datesignaturemarche = $datesignaturemarche;
        return $this;
    }

    public function getDateordreservice(): ?\DateTimeInterface
    {
        return $this->dateordreservice;
    }

    public function setDateordreservice(?\DateTimeInterface $dateordreservice): static
    {
        $this->dateordreservice = $dateordreservice;
        return $this;
    }

    public function getDateavenantprolongation(): ?\DateTimeInterface
    {
        return $this->dateavenantprolongation;
    }

    public function setDateavenantprolongation(?\DateTimeInterface $dateavenantprolongation): static
    {
        $this->dateavenantprolongation = $dateavenantprolongation;
        return $this;
    }

    public function getNbmoisdelaicontractuel(): ?float
    {
        return $this->nbmoisdelaicontractuel;
    }

    public function setNbmoisdelaicontractuel(?float $nbmoisdelaicontractuel): static
    {
        $this->nbmoisdelaicontractuel = $nbmoisdelaicontractuel;
        return $this;
    }

    public function getNbmoisperiodepreparation(): ?float
    {
        return $this->nbmoisperiodepreparation;
    }

    public function setNbmoisperiodepreparation(?float $nbmoisperiodepreparation): static
    {
        $this->nbmoisperiodepreparation = $nbmoisperiodepreparation;
        return $this;
    }

    public function getIdtypeprojet(): ?int
    {
        return $this->idtypeprojet;
    }

    public function setIdtypeprojet(?int $idtypeprojet): static
    {
        $this->idtypeprojet = $idtypeprojet;
        return $this;
    }

    public function getDatelimiteremiseprojet(): ?\DateTimeInterface
    {
        return $this->datelimiteremiseprojet;
    }

    public function setDatelimiteremiseprojet(?\DateTimeInterface $datelimiteremiseprojet): static
    {
        $this->datelimiteremiseprojet = $datelimiteremiseprojet;
        return $this;
    }

    public function getDateenvoiremiseprojet(): ?\DateTimeInterface
    {
        return $this->dateenvoiremiseprojet;
    }

    public function setDateenvoiremiseprojet(?\DateTimeInterface $dateenvoiremiseprojet): static
    {
        $this->dateenvoiremiseprojet = $dateenvoiremiseprojet;
        return $this;
    }

    public function getIdtypeenvoiremiseprojet(): ?int
    {
        return $this->idtypeenvoiremiseprojet;
    }

    public function setIdtypeenvoiremiseprojet(?int $idtypeenvoiremiseprojet): static
    {
        $this->idtypeenvoiremiseprojet = $idtypeenvoiremiseprojet;
        return $this;
    }

    public function getEmetteurenvoiremiseprojet(): ?int
    {
        return $this->emetteurenvoiremiseprojet;
    }

    public function setEmetteurenvoiremiseprojet(?int $emetteurenvoiremiseprojet): static
    {
        $this->emetteurenvoiremiseprojet = $emetteurenvoiremiseprojet;
        return $this;
    }

    public function getCheminrelatifprojet(): ?string
    {
        return $this->cheminrelatifprojet;
    }

    public function setCheminrelatifprojet(?string $cheminrelatifprojet): static
    {
        $this->cheminrelatifprojet = $cheminrelatifprojet;
        return $this;
    }

    public function getIdpereprojet(): ?int
    {
        return $this->idpereprojet;
    }

    public function setIdpereprojet(?int $idpereprojet): static
    {
        $this->idpereprojet = $idpereprojet;
        return $this;
    }

    public function getInterneprojet(): ?bool
    {
        return $this->interneprojet;
    }

    public function setInterneprojet(?bool $interneprojet): static
    {
        $this->interneprojet = $interneprojet;
        return $this;
    }

    public function getTagplanifiable(): ?bool
    {
        return $this->tagplanifiable;
    }

    public function setTagplanifiable(?bool $tagplanifiable): static
    {
        $this->tagplanifiable = $tagplanifiable;
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

    public function getDatevisite(): ?\DateTimeInterface
    {
        return $this->datevisite;
    }

    public function setDatevisite(?\DateTimeInterface $datevisite): static
    {
        $this->datevisite = $datevisite;
        return $this;
    }

    public function getIdvisiteur(): ?int
    {
        return $this->idvisiteur;
    }

    public function setIdvisiteur(?int $idvisiteur): static
    {
        $this->idvisiteur = $idvisiteur;
        return $this;
    }

    public function getTagprojet(): ?string
    {
        return $this->tagprojet;
    }

    public function setTagprojet(?string $tagprojet): static
    {
        $this->tagprojet = $tagprojet;
        return $this;
    }

    public function getEstprojetconsolide(): ?bool
    {
        return $this->estprojetconsolide;
    }

    public function setEstprojetconsolide(?bool $estprojetconsolide): static
    {
        $this->estprojetconsolide = $estprojetconsolide;
        return $this;
    }

    public function getModepropositionfacturation(): ?bool
    {
        return $this->modepropositionfacturation;
    }

    public function setModepropositionfacturation(?bool $modepropositionfacturation): static
    {
        $this->modepropositionfacturation = $modepropositionfacturation;
        return $this;
    }

    public function getDatereglementaireouverture(): ?\DateTimeInterface
    {
        return $this->datereglementaireouverture;
    }

    public function setDatereglementaireouverture(?\DateTimeInterface $datereglementaireouverture): static
    {
        $this->datereglementaireouverture = $datereglementaireouverture;
        return $this;
    }

    public function getIdbanquecaisse(): ?int
    {
        return $this->idbanquecaisse;
    }

    public function setIdbanquecaisse(?int $idbanquecaisse): static
    {
        $this->idbanquecaisse = $idbanquecaisse;
        return $this;
    }
}
