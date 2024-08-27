<?php

namespace App\Entity;

use App\Repository\ItemRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ItemRepository::class)]
class Item extends BaseEntity
{
    #[ORM\Column(length: 255)]
    #[Groups(['item:read', 'item:write'])]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['item:read', 'item:write'])]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    #[Groups(['item:read', 'item:write'])]
    private ?string $startingPrice = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['item:read', 'item:write'])]
    private ?\DateTimeInterface $auctionStartTime = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['item:read', 'item:write'])]
    private ?\DateTimeInterface $auctionEndTime = null;

    /**
     * @var Collection<int, ItemMedia>
     */
    #[ORM\OneToMany(targetEntity: ItemMedia::class, mappedBy: 'item', orphanRemoval: true)]
    #[Groups(['item:read'])]
    private Collection $medias;

    /**
     * @var Collection<int, Bid>
     */
    #[ORM\OneToMany(targetEntity: Bid::class, mappedBy: 'item', orphanRemoval: true)]
    private Collection $bids;

    /**
     * @var Collection<int, AutoBid>
     */
    #[ORM\OneToMany(targetEntity: AutoBid::class, mappedBy: 'item', orphanRemoval: true)]
    private Collection $autoBids;

    public function __construct()
    {
        parent::__construct();
        $this->medias = new ArrayCollection();
        $this->bids = new ArrayCollection();
        $this->autoBids = new ArrayCollection();
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getStartingPrice(): ?string
    {
        return $this->startingPrice;
    }

    public function setStartingPrice(?string $startingPrice): static
    {
        $this->startingPrice = $startingPrice;

        return $this;
    }

    public function getAuctionStartTime(): ?\DateTimeInterface
    {
        return $this->auctionStartTime;
    }

    public function setAuctionStartTime(?\DateTimeInterface $auctionStartTime): static
    {
        $this->auctionStartTime = $auctionStartTime;

        return $this;
    }

    public function getAuctionEndTime(): ?\DateTimeInterface
    {
        return $this->auctionEndTime;
    }

    public function setAuctionEndTime(?\DateTimeInterface $auctionEndTime): static
    {
        $this->auctionEndTime = $auctionEndTime;

        return $this;
    }

    /**
     * @return Collection<int, ItemMedia>
     */
    public function getMedias(): Collection
    {
        return $this->medias;
    }

    public function addMedia(ItemMedia $media): static
    {
        if (! $this->medias->contains($media)) {
            $this->medias->add($media);
            $media->setItem($this);
        }

        return $this;
    }

    public function removeMedia(ItemMedia $media): static
    {
        if ($this->medias->removeElement($media)) {
            // set the owning side to null (unless already changed)
            if ($media->getItem() === $this) {
                $media->setItem(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Bid>
     */
    public function getBids(): Collection
    {
        return $this->bids;
    }

    public function addBid(Bid $bid): static
    {
        if (! $this->bids->contains($bid)) {
            $this->bids->add($bid);
            $bid->setItem($this);
        }

        return $this;
    }

    public function removeBid(Bid $bid): static
    {
        if ($this->bids->removeElement($bid)) {
            // set the owning side to null (unless already changed)
            if ($bid->getItem() === $this) {
                $bid->setItem(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AutoBid>
     */
    public function getAutoBids(): Collection
    {
        return $this->autoBids;
    }

    public function addAutoBid(AutoBid $autoBid): static
    {
        if (! $this->autoBids->contains($autoBid)) {
            $this->autoBids->add($autoBid);
            $autoBid->setItem($this);
        }

        return $this;
    }

    public function removeAutoBid(AutoBid $autoBid): static
    {
        if ($this->autoBids->removeElement($autoBid)) {
            // set the owning side to null (unless already changed)
            if ($autoBid->getItem() === $this) {
                $autoBid->setItem(null);
            }
        }

        return $this;
    }
}
