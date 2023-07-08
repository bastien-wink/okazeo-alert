<?php

namespace App\Entity;

use App\Repository\SubscriptionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SubscriptionRepository::class)]
#[UniqueEntity(fields: ['email'], message: 'Cette adresse email est déjà utilisée')]
class Subscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $key = null;

    #[ORM\Column(options: ['default' => 1])]
    #[Assert\Choice([1, 12, 24])]
    private int $frequency = 24;

    #[ORM\Column(length: 255)]
    #[Assert\Email]
    #[Assert\NotNull(message: 'Cette valeur ne peut pas être vide.')]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lastAnnonceUrl = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotNull(message: 'Cette valeur ne peut pas être vide.')]
    #[Assert\Regex(pattern: '/^\d{4,5}$/', message: 'Code postal invalide')]
    private ?string $filterZipcode = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotNull(message: 'Cette valeur ne peut pas être vide.')]
    #[Assert\Regex(pattern: '/^\d+$/', message: 'Code postal invalide')]
    private ?string $filterRange = '10';

    #[Assert\Regex(pattern: '/^\d+$/', message: 'Rang invalide')]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $filterMinRank = '1000';

    #[Assert\Regex(pattern: '/^\d+$/', message: 'Année invalide')]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $filterMinYear = '2010';

    /**
     * @var string[]
     */
    #[ORM\Column(type: 'json')]
    private array $excludedGames = [];

    public function __construct()
    {
        $this->key = md5(random_bytes(100));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getFilterZipcode(): ?string
    {
        return $this->filterZipcode;
    }

    public function setFilterZipcode(string $filterZipcode): self
    {
        $this->filterZipcode = $filterZipcode;

        return $this;
    }

    public function getFilterRange(): ?string
    {
        return $this->filterRange;
    }

    public function setFilterRange(?string $filterRange): self
    {
        $this->filterRange = $filterRange;

        return $this;
    }

    public function getFilterMinRank(): ?string
    {
        return $this->filterMinRank;
    }

    public function setFilterMinRank(?string $filterMinRank): self
    {
        $this->filterMinRank = $filterMinRank;

        return $this;
    }

    public function getFilterMinYear(): ?string
    {
        return $this->filterMinYear;
    }

    public function setFilterMinYear(?string $filterMinYear): self
    {
        $this->filterMinYear = $filterMinYear;

        return $this;
    }

    /**
     * @param string[] $excludedGames
     */
    public function setExcludedGames(array $excludedGames): Subscription
    {
        $this->excludedGames = $excludedGames;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getExcludedGames(): array
    {
        return $this->excludedGames;
    }

    public function setKey(?string $key): Subscription
    {
        $this->key = $key;

        return $this;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function setLastAnnonceUrl(?string $lastAnnonceUrl): Subscription
    {
        $this->lastAnnonceUrl = $lastAnnonceUrl;

        return $this;
    }

    public function getLastAnnonceUrl(): ?string
    {
        return $this->lastAnnonceUrl;
    }

    public function setFrequency(int $frequency): Subscription
    {
        $this->frequency = $frequency;

        return $this;
    }

    public function getFrequency(): int
    {
        return $this->frequency;
    }
}
