<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'PrestationContratNotePlanning')]
class Prestationcontratnoteplanning
{
    #[ORM\Id]
    #[ORM\Column(name: 'IdPrestationContrat', type: 'bigint')]
    private int $idprestationcontrat;

    #[ORM\Id]
    #[ORM\Column(name: 'MoisInterventionPrestationContratCadencier', type: 'integer')]
    private int $moisinterventionprestationcontratcadencier;

    #[ORM\Id]
    #[ORM\Column(name: 'IdContratPeriode', type: 'bigint')]
    private int $idcontratperiode;

    #[ORM\Column(name: 'NotePrestationContratNotePlanning', type: 'string', nullable: true, length: 300)]
    private ?string $noteprestationcontratnoteplanning = null;

    public function getIdprestationcontrat(): int
    {
        return $this->idprestationcontrat;
    }

    public function setIdprestationcontrat(int $idprestationcontrat): static
    {
        $this->idprestationcontrat = $idprestationcontrat;
        return $this;
    }

    public function getMoisinterventionprestationcontratcadencier(): int
    {
        return $this->moisinterventionprestationcontratcadencier;
    }

    public function setMoisinterventionprestationcontratcadencier(int $moisinterventionprestationcontratcadencier): static
    {
        $this->moisinterventionprestationcontratcadencier = $moisinterventionprestationcontratcadencier;
        return $this;
    }

    public function getIdcontratperiode(): int
    {
        return $this->idcontratperiode;
    }

    public function setIdcontratperiode(int $idcontratperiode): static
    {
        $this->idcontratperiode = $idcontratperiode;
        return $this;
    }

    public function getNoteprestationcontratnoteplanning(): ?string
    {
        return $this->noteprestationcontratnoteplanning;
    }

    public function setNoteprestationcontratnoteplanning(?string $noteprestationcontratnoteplanning): static
    {
        $this->noteprestationcontratnoteplanning = $noteprestationcontratnoteplanning;
        return $this;
    }
}
