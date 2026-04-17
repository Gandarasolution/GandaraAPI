<?php

namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\AffectationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AffectationRepository::class)]
#[Assert\Expression(
    "(this.getIdSalarie() == null and this.getIdInterim() != null) or (this.getIdSalarie() != null and this.getIdInterim() == null)",
    message: "Une affectation doit concerner soit un salarié, soit un intérimaire, mais pas les deux."
)]
#[ORM\Table(name: 'Affectation')]
class Affectation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'IdAffectation', type: 'bigint')]
    private ?int $IdAffectation = null;

    #[ORM\OneToOne(targetEntity: Salarie::class)]
    #[ORM\JoinColumn(name: "IdSalarie", referencedColumnName: "IdSalarie", nullable: true)]
    private ?Salarie $IdSalarie = null;

    #[ORM\OneToOne(targetEntity: Interim::class)]
    #[ORM\JoinColumn(name: "IdInterim", referencedColumnName: "IdInterim", nullable: true)]
    private ?Interim $IdInterim = null;

    public function getId(): ?int
    {
        return $this->IdAffectation;
    }

    public function getIdSalarie(): ?Salarie
    {
        return $this->IdSalarie;
    }

    public function setIdSalarie(?Salarie $IdSalarie): static
    {
        $this->IdSalarie = $IdSalarie;

        return $this;
    }

    public function getIdInterim(): ?Interim
    {
        return $this->IdInterim;
    }

    public function setIdInterim(?Interim $IdInterim): static
    {
        $this->IdInterim = $IdInterim;

        return $this;
    }

}
