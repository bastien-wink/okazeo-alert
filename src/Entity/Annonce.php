<?php

namespace App\Entity;

use App\Repository\AnnonceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AnnonceRepository::class)]
class Annonce
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $url = null;

    #[ORM\Column(length: 1000)]
    private ?string $imageUrl = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $price = null;

    #[ORM\ManyToOne(targetEntity: Game::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Game $game = null;

    public function __construct(
        string $url,
        string $imageUrl,
        string $name,
        $price,
    ) {
        $this->url = $url;
        $this->imageUrl = $imageUrl;
        $this->name = $name;
        $this->price = $price;
    }

    public function __toArray()
    {
        return [
            'url' => $this->getUrl(),
            'name' => $this->getName(),
            'price' => $this->getPrice(),
            'bggUrl' => "https://boardgamegeek.com/boardgame/{$this->getGame()->getBggId()} ",
            'bggRank' => $this->getGame()->getBggRank(),
            'bggName' => $this->getGame()->getBggName(),
            'yearpublished' => $this->getGame()->getBggYearPublished(),
            'playingtime' => $this->getGame()->getBggPlayingTime(),
            'boardgamedesigner' => $this->getGame()->getBggDesigner(),
            'averageweight' => $this->getGame()->getBggWeight(),
        ];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(string $imageUrl): self
    {
        $this->imageUrl = $imageUrl;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getGame(): ?Game
    {
        return $this->game;
    }

    public function setGame(?Game $game): Annonce
    {
        $this->game = $game;

        return $this;
    }

    public function setPrice(?string $price): Annonce
    {
        $this->price = $price;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }
}
