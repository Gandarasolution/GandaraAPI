<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'Equipe')]
class Equipe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'IdEquipe', type: 'bigint')]
    private int $idequipe;

    #[ORM\Column(name: 'DesignationEquipe', type: 'string', length: 150)]
    private string $designationequipe;

    public function getIdequipe(): int
    {
        return $this->idequipe;
    }

    public function setIdequipe(int $idequipe): static
    {
        $this->idequipe = $idequipe;
        return $this;
    }

    public function getDesignationequipe(): string
    {
        return $this->designationequipe;
    }

    public function setDesignationequipe(string $designationequipe): static
    {
        $this->designationequipe = $designationequipe;
        return $this;
    }
}
