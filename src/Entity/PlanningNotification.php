<?php

namespace App\Entity;

use App\Repository\PlanningNotificationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlanningNotificationRepository::class)]
class PlanningNotification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $IdNotification = null;

    #[ORM\ManyToOne(targetEntity: Session::class)]
    #[ORM\JoinColumn(name: "IdSession", referencedColumnName: "IdPersonnel", nullable: true)]
    private Session $IdPersonnel;

    #[ORM\Column(length: 255)]
    private ?string $LibelleNotifications = null;

    #[ORM\Column(length: 255)]
    private ?string $TitreNotifications = null;

    #[ORM\Column]
    private ?\DateTime $DateNotifications = null;

    #[ORM\Column]
    private ?bool $LueNotifications = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: "IdTypeNotification", referencedColumnName: "IdTypeNotification", nullable: true)]
    private ?PlanningNotificationType $IdTypeNotifications = null;

    public function getId(): ?int
    {
        return $this->IdNotification;
    }

    public function getIdPersonnel(): ?Session
    {
        return $this->IdPersonnel;
    }

    public function setIdPersonnel(?Session $IdPersonnel): static
    {
        $this->IdPersonnel = $IdPersonnel;

        return $this;
    }

    public function getLibelleNotifications(): ?string
    {
        return $this->LibelleNotifications;
    }

    public function setLibelleNotifications(string $LibelleNotifications): static
    {
        $this->LibelleNotifications = $LibelleNotifications;

        return $this;
    }

    public function getTitreNotifications(): ?string
    {
        return $this->TitreNotifications;
    }

    public function setTitreNotifications(string $TitreNotifications): static
    {
        $this->TitreNotifications = $TitreNotifications;

        return $this;
    }

    public function getDateNotifications(): ?\DateTime
    {
        return $this->DateNotifications;
    }

    public function setDateNotifications(\DateTime $DateNotifications): static
    {
        $this->DateNotifications = $DateNotifications;

        return $this;
    }

    public function isLueNotifications(): ?bool
    {
        return $this->LueNotifications;
    }

    public function setLueNotifications(bool $LueNotifications): static
    {
        $this->LueNotifications = $LueNotifications;

        return $this;
    }

    public function getIdTypeNotifications(): ?PlanningNotificationType
    {
        return $this->IdTypeNotifications;
    }

    public function setIdTypeNotifications(?PlanningNotificationType $IdTypeNotifications): static
    {
        $this->IdTypeNotifications = $IdTypeNotifications;

        return $this;
    }
}
