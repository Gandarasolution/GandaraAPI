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

    #[ORM\Column(name: 'LicencePlanning', type: 'boolean', nullable: true)]
    private ?string $licenceplanning = null;



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

    public function getLicenceplanning(): ?string
    {
        return $this->licenceplanning;
    }

    public function setLicenceplanning(?string $licenceplanning): static
    {
        $this->licenceplanning = $licenceplanning;
        return $this;
    }


    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function getUserIdentifier(): string
    {
        return $this->idpersonnel;
    }

}
