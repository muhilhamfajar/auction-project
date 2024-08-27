<?php

namespace App\Entity;

use App\Repository\BidConfigRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: BidConfigRepository::class)]
class BidConfig extends BaseEntity
{
    #[ORM\OneToOne(inversedBy: 'bidConfig', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['bid_config:read', 'bid_config:write'])]
    private ?User $user = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups(['bid_config:read', 'bid_config:write'])]
    private ?string $maxBidAmount = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['bid_config:read', 'bid_config:write'])]
    private ?int $bidAlertPercentage = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    #[Groups(['bid_config:read'])]
    private ?string $reservedAmount = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ["default" => false])]
    private ?bool $alertSent = false;

    public function __construct()
    {
        parent::__construct();
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getMaxBidAmount(): ?string
    {
        return $this->maxBidAmount;
    }

    public function setMaxBidAmount(string $maxBidAmount): static
    {
        $this->maxBidAmount = $maxBidAmount;

        return $this;
    }

    public function getBidAlertPercentage(): ?int
    {
        return $this->bidAlertPercentage;
    }

    public function setBidAlertPercentage(?int $bidAlertPercentage): static
    {
        $this->bidAlertPercentage = $bidAlertPercentage;

        return $this;
    }

    public function getReservedAmount(): ?string
    {
        return $this->reservedAmount;
    }

    public function setReservedAmount(?string $reservedAmount): static
    {
        $this->reservedAmount = $reservedAmount;

        return $this;
    }

    public function isAlertSent(): bool
    {
        return $this->alertSent;
    }

    public function setAlertSent(bool $alertSent): self
    {
        $this->alertSent = $alertSent;
        return $this;
    }
}
