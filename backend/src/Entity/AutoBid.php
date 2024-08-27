<?php

namespace App\Entity;

use App\Repository\AutoBidRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: AutoBidRepository::class)]
#[UniqueEntity(
    fields: ['item', 'user'],
    message: 'The user already activate auto bid on this item.',
    errorPath: 'item',
)]
class AutoBid extends BaseEntity
{
    #[ORM\ManyToOne(inversedBy: 'autoBids')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['auto_bid:read'])]
    private ?Item $item = null;

    #[ORM\ManyToOne(inversedBy: 'autoBids')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['auto_bid:read'])]
    private ?User $user = null;

    public function getItem(): ?Item
    {
        return $this->item;
    }

    public function setItem(?Item $item): static
    {
        $this->item = $item;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }
}
