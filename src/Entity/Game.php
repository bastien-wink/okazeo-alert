<?php

namespace App\Entity;

use App\Repository\GameRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameRepository::class)]
class Game
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $okkazeoName = null;

    #[ORM\Column]
    private ?int $okkazeoId = null;

    #[ORM\Column(length: 255)]
    private ?string $okkazeoImageUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $bggName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $bggWeight = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $bggRank = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $bggYearPublished = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $bggPlayingTime = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $bggDesigner = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $bggId = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOkkazeoName(): ?string
    {
        return $this->okkazeoName;
    }

    public function setOkkazeoName(string $okkazeoName): self
    {
        $this->okkazeoName = $okkazeoName;

        return $this;
    }

    public function getOkkazeoId(): ?int
    {
        return $this->okkazeoId;
    }

    public function setOkkazeoId(int $okkazeoId): self
    {
        $this->okkazeoId = $okkazeoId;

        return $this;
    }

    public function getBggName(): ?string
    {
        return $this->bggName;
    }

    public function setBggName(?string $bggName): self
    {
        $this->bggName = $bggName;

        return $this;
    }

    public function getBggWeight(): ?string
    {
        return $this->bggWeight;
    }

    public function setBggWeight(?string $bggWeight): self
    {
        $this->bggWeight = $bggWeight;

        return $this;
    }

    public function getBggYearPublished(): ?string
    {
        return $this->bggYearPublished;
    }

    public function setBggYearPublished(string $bggYearPublished): self
    {
        $this->bggYearPublished = $bggYearPublished;

        return $this;
    }

    public function getBggDesigner(): ?string
    {
        return $this->bggDesigner;
    }

    public function setBggDesigner(?string $bggDesigner): self
    {
        $this->bggDesigner = $bggDesigner;

        return $this;
    }

    public function getBggId(): ?string
    {
        return $this->bggId;
    }

    public function setBggId(?string $bggId): self
    {
        $this->bggId = $bggId;

        return $this;
    }

    public function getOkkazeoImageUrl(): ?string
    {
        return $this->okkazeoImageUrl;
    }

    public function setOkkazeoImageUrl(?string $okkazeoImageUrl): Game
    {
        $this->okkazeoImageUrl = $okkazeoImageUrl;

        return $this;
    }

    public function setBggRank(?string $bggRank): Game
    {
        $this->bggRank = $bggRank;

        return $this;
    }

    public function getBggRank(): ?string
    {
        return $this->bggRank;
    }

    public function setBggPlayingTime(?string $bggPlayingTime): Game
    {
        $this->bggPlayingTime = $bggPlayingTime;

        return $this;
    }

    public function getBggPlayingTime(): ?string
    {
        return $this->bggPlayingTime;
    }
}
