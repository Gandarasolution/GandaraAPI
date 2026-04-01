<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'Image')]
class Image
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'IdImage', type: 'bigint')]
    private int $idimage;

    #[ORM\Column(name: 'InkID', type: 'guid')]
    private string $inkid;

    #[ORM\Column(name: 'Ink', type: 'blob')]
    private string $ink;

    #[ORM\Column(name: 'Length', type: 'integer')]
    private int $length;

    #[ORM\Column(name: 'LeftBound', type: 'integer', nullable: true)]
    private ?int $leftbound = null;

    #[ORM\Column(name: 'TopBound', type: 'integer', nullable: true)]
    private ?int $topbound = null;

    #[ORM\Column(name: 'RightBound', type: 'integer', nullable: true)]
    private ?int $rightbound = null;

    #[ORM\Column(name: 'BottomBound', type: 'integer', nullable: true)]
    private ?int $bottombound = null;

    #[ORM\Column(name: 'rowguid', type: 'guid')]
    private string $rowguid;

    public function getIdimage(): int
    {
        return $this->idimage;
    }

    public function setIdimage(int $idimage): static
    {
        $this->idimage = $idimage;
        return $this;
    }

    public function getInkid(): string
    {
        return $this->inkid;
    }

    public function setInkid(string $inkid): static
    {
        $this->inkid = $inkid;
        return $this;
    }

    public function getInk(): string
    {
        return $this->ink;
    }

    public function setInk(string $ink): static
    {
        if (is_resource($ink)) {
            $this->ink = stream_get_contents($ink);
        } else {
            $this->ink = $ink;
        }
        return $this;
    }

    public function getLength(): int
    {
        return $this->length;
    }

    public function setLength(int $length): static
    {
        $this->length = $length;
        return $this;
    }

    public function getLeftbound(): ?int
    {
        return $this->leftbound;
    }

    public function setLeftbound(?int $leftbound): static
    {
        $this->leftbound = $leftbound;
        return $this;
    }

    public function getTopbound(): ?int
    {
        return $this->topbound;
    }

    public function setTopbound(?int $topbound): static
    {
        $this->topbound = $topbound;
        return $this;
    }

    public function getRightbound(): ?int
    {
        return $this->rightbound;
    }

    public function setRightbound(?int $rightbound): static
    {
        $this->rightbound = $rightbound;
        return $this;
    }

    public function getBottombound(): ?int
    {
        return $this->bottombound;
    }

    public function setBottombound(?int $bottombound): static
    {
        $this->bottombound = $bottombound;
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
}
