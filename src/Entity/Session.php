<?php

namespace App\Entity;


use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'Session')]
class Session implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(name: 'IdPersonnel', type: 'bigint')]
    private int $idpersonnel;

    #[ORM\Column(name: 'Password', type: 'string', nullable: true, length: 50)]
    private ?string $password = null;

    #[ORM\Column(name: 'rowguid', type: 'guid')]
    private string $rowguid;

    #[ORM\Column(name: 'TypeSession', type: 'string', nullable: true, length: 10)]
    private ?string $typesession = null;

    #[ORM\Column(name: 'Mail', type: 'string', nullable: true, length: 150)]
    private ?string $mail = null;

    #[ORM\Column(name: 'CardRFID', type: 'string', nullable: true, length: 10)]
    private ?string $cardrfid = null;

    #[ORM\Column(name: 'LicenceGandaraWindows', type: 'boolean', nullable: true)]
    private ?bool $licencegandarawindows = null;

    #[ORM\Column(name: 'LicenceExtranet', type: 'boolean', nullable: true)]
    private ?bool $licenceextranet = null;

    #[ORM\Column(name: 'LicenceMobilite', type: 'boolean', nullable: true)]
    private ?bool $licencemobilite = null;

    #[ORM\Column(name: 'FreeLicenceGandaraWindows', type: 'boolean', nullable: true)]
    private ?bool $freelicencegandarawindows = null;

    #[ORM\Column(name: 'FreeLicenceExtranet', type: 'boolean', nullable: true)]
    private ?bool $freelicenceextranet = null;

    #[ORM\Column(name: 'FreeLicenceMobilite', type: 'boolean', nullable: true)]
    private ?bool $freelicencemobilite = null;

    #[ORM\Column(name: 'VirtuelSession', type: 'boolean', nullable: true)]
    private ?bool $virtuelsession = null;

    #[ORM\Column(name: 'SimultaneeSession', type: 'integer', nullable: true)]
    private ?int $simultaneesession = null;

    #[ORM\Column(name: 'LicenceMobileMagasin', type: 'boolean')]
    private bool $licencemobilemagasin;

    #[ORM\Column(name: 'LicenceMobilePhoto', type: 'boolean')]
    private bool $licencemobilephoto;

    #[ORM\Column(name: 'LicenceMobileCommande', type: 'boolean')]
    private bool $licencemobilecommande;

    #[ORM\Column(name: 'LicenceMobileInventaireTournant', type: 'boolean')]
    private bool $licencemobileinventairetournant;

    #[ORM\Column(name: 'FreeLicenceMobileMagasin', type: 'boolean', nullable: true)]
    private ?bool $freelicencemobilemagasin = null;

    #[ORM\Column(name: 'FreeLicenceMobilePhoto', type: 'boolean', nullable: true)]
    private ?bool $freelicencemobilephoto = null;

    #[ORM\Column(name: 'FreeLicenceMobileCommande', type: 'boolean', nullable: true)]
    private ?bool $freelicencemobilecommande = null;

    #[ORM\Column(name: 'FreeLicenceMobileInventaireTournant', type: 'boolean', nullable: true)]
    private ?bool $freelicencemobileinventairetournant = null;

    public function getIdpersonnel(): int
    {
        return $this->idpersonnel;
    }

    public function setIdpersonnel(int $idpersonnel): static
    {
        $this->idpersonnel = $idpersonnel;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): static
    {
        $this->password = $password;
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

    public function getTypesession(): ?string
    {
        return $this->typesession;
    }

    public function setTypesession(?string $typesession): static
    {
        $this->typesession = $typesession;
        return $this;
    }

    public function getMail(): ?string
    {
        return $this->mail;
    }

    public function setMail(?string $mail): static
    {
        $this->mail = $mail;
        return $this;
    }

    public function getCardrfid(): ?string
    {
        return $this->cardrfid;
    }

    public function setCardrfid(?string $cardrfid): static
    {
        $this->cardrfid = $cardrfid;
        return $this;
    }

    public function getLicencegandarawindows(): ?bool
    {
        return $this->licencegandarawindows;
    }

    public function setLicencegandarawindows(?bool $licencegandarawindows): static
    {
        $this->licencegandarawindows = $licencegandarawindows;
        return $this;
    }

    public function getLicenceextranet(): ?bool
    {
        return $this->licenceextranet;
    }

    public function setLicenceextranet(?bool $licenceextranet): static
    {
        $this->licenceextranet = $licenceextranet;
        return $this;
    }

    public function getLicencemobilite(): ?bool
    {
        return $this->licencemobilite;
    }

    public function setLicencemobilite(?bool $licencemobilite): static
    {
        $this->licencemobilite = $licencemobilite;
        return $this;
    }

    public function getFreelicencegandarawindows(): ?bool
    {
        return $this->freelicencegandarawindows;
    }

    public function setFreelicencegandarawindows(?bool $freelicencegandarawindows): static
    {
        $this->freelicencegandarawindows = $freelicencegandarawindows;
        return $this;
    }

    public function getFreelicenceextranet(): ?bool
    {
        return $this->freelicenceextranet;
    }

    public function setFreelicenceextranet(?bool $freelicenceextranet): static
    {
        $this->freelicenceextranet = $freelicenceextranet;
        return $this;
    }

    public function getFreelicencemobilite(): ?bool
    {
        return $this->freelicencemobilite;
    }

    public function setFreelicencemobilite(?bool $freelicencemobilite): static
    {
        $this->freelicencemobilite = $freelicencemobilite;
        return $this;
    }

    public function getVirtuelsession(): ?bool
    {
        return $this->virtuelsession;
    }

    public function setVirtuelsession(?bool $virtuelsession): static
    {
        $this->virtuelsession = $virtuelsession;
        return $this;
    }

    public function getSimultaneesession(): ?int
    {
        return $this->simultaneesession;
    }

    public function setSimultaneesession(?int $simultaneesession): static
    {
        $this->simultaneesession = $simultaneesession;
        return $this;
    }

    public function getLicencemobilemagasin(): bool
    {
        return $this->licencemobilemagasin;
    }

    public function setLicencemobilemagasin(bool $licencemobilemagasin): static
    {
        $this->licencemobilemagasin = $licencemobilemagasin;
        return $this;
    }

    public function getLicencemobilephoto(): bool
    {
        return $this->licencemobilephoto;
    }

    public function setLicencemobilephoto(bool $licencemobilephoto): static
    {
        $this->licencemobilephoto = $licencemobilephoto;
        return $this;
    }

    public function getLicencemobilecommande(): bool
    {
        return $this->licencemobilecommande;
    }

    public function setLicencemobilecommande(bool $licencemobilecommande): static
    {
        $this->licencemobilecommande = $licencemobilecommande;
        return $this;
    }

    public function getLicencemobileinventairetournant(): bool
    {
        return $this->licencemobileinventairetournant;
    }

    public function setLicencemobileinventairetournant(bool $licencemobileinventairetournant): static
    {
        $this->licencemobileinventairetournant = $licencemobileinventairetournant;
        return $this;
    }

    public function getFreelicencemobilemagasin(): ?bool
    {
        return $this->freelicencemobilemagasin;
    }

    public function setFreelicencemobilemagasin(?bool $freelicencemobilemagasin): static
    {
        $this->freelicencemobilemagasin = $freelicencemobilemagasin;
        return $this;
    }

    public function getFreelicencemobilephoto(): ?bool
    {
        return $this->freelicencemobilephoto;
    }

    public function setFreelicencemobilephoto(?bool $freelicencemobilephoto): static
    {
        $this->freelicencemobilephoto = $freelicencemobilephoto;
        return $this;
    }

    public function getFreelicencemobilecommande(): ?bool
    {
        return $this->freelicencemobilecommande;
    }

    public function setFreelicencemobilecommande(?bool $freelicencemobilecommande): static
    {
        $this->freelicencemobilecommande = $freelicencemobilecommande;
        return $this;
    }

    public function getFreelicencemobileinventairetournant(): ?bool
    {
        return $this->freelicencemobileinventairetournant;
    }

    public function setFreelicencemobileinventairetournant(?bool $freelicencemobileinventairetournant): static
    {
        $this->freelicencemobileinventairetournant = $freelicencemobileinventairetournant;
        return $this;
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->idpersonnel;
    }

}
