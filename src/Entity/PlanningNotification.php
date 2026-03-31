<?php

namespace App\Entity;

use App\Repository\PlanningNotificationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlanningNotificationRepository::class)]
#[ORM\Table(name: 'PlanningNotification')]
class PlanningNotification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'IdNotification', type: 'integer')]
    private ?int $IdNotification = null;

    #[ORM\ManyToOne(targetEntity: Session::class)]
    #[ORM\JoinColumn(name: "IdSession", referencedColumnName: "IdPersonnel", nullable: true)]
    private Session $IdPersonnel;

    #[ORM\Column(name: 'LibelleNotifications', length: 255, nullable: false)]
    private ?string $LibelleNotifications = null;

    #[ORM\Column(name: 'TitreNotifications', length: 255, nullable: false)]
    private ?string $TitreNotifications = null;

    #[ORM\Column(name: 'DateNotifications', type: 'datetime', length: 255, nullable: false)]
    private ?\DateTime $DateNotifications = null;

    #[ORM\Column(name: 'LueNotifications', length: 255, nullable: false)]
    private ?bool $LueNotifications = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: "IdTypeNotification", referencedColumnName: "IdPlanningNotificationType", nullable: true)]
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
