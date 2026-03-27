<?php

namespace App\Entity;

use App\Repository\PlanningNotificationTypeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlanningNotificationTypeRepository::class)]
class PlanningNotificationType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'IdPlanningNotificationType', type: 'bigint')]
    private ?int $IdTypeNotification = null;

    #[ORM\Column(length: 255)]
    private ?string $LabelTypeNotifications = null;

    public function getId(): ?int
    {
        return $this->IdTypeNotification;
    }

    public function getLabelTypeNotifications(): ?string
    {
        return $this->LabelTypeNotifications;
    }

    public function setLabelTypeNotifications(string $LabelTypeNotifications): static
    {
        $this->LabelTypeNotifications = $LabelTypeNotifications;

        return $this;
    }
}
