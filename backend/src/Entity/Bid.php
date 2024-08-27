<?php

namespace App\Entity;

use App\Repository\BidRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: BidRepository::class)]
class Bid extends BaseEntity
{
    #[ORM\ManyToOne(inversedBy: 'bids')]
    #[Groups(['bid:read'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $bidder = null;

    #[ORM\ManyToOne(inversedBy: 'bids')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Item $item = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['bid:read'])]
    private ?\DateTimeInterface $bidTime = null;

    #[ORM\Column]
    #[Groups(['bid:read'])]
    private ?bool $isAutoBid = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups(['bid:read'])]
    private ?string $amount = null;

    public function getBidder(): ?User
    {
        return $this->bidder;
    }

    public function setBidder(?User $bidder): static
    {
        $this->bidder = $bidder;

        return $this;
    }

    public function getItem(): ?Item
    {
        return $this->item;
    }

    public function setItem(?Item $item): static
    {
        $this->item = $item;

        return $this;
    }

    public function getBidTime(): ?\DateTimeInterface
    {
        return $this->bidTime;
    }

    public function setBidTime(?\DateTimeInterface $bidTime): static
    {
        $this->bidTime = $bidTime;

        return $this;
    }

    public function isAutoBid(): ?bool
    {
        return $this->isAutoBid;
    }

    public function setIsAutoBid(bool $isAutoBid): static
    {
        $this->isAutoBid = $isAutoBid;

        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;

        return $this;
    }
}
