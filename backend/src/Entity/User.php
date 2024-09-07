<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_USERNAME', fields: ['username'])]
class User extends BaseEntity implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Column(length: 180, unique: true)]
    #[Groups(['user:read', 'user:write', 'base:read'])]
    #[Assert\NotBlank(message: 'Username is required')]
    #[Assert\Length(
        min: 3,
        max: 180,
        minMessage: 'Username must be at least {{ limit }} characters long',
        maxMessage: 'Username cannot be longer than {{ limit }} characters'
    )]
    private ?string $username = null;

    #[ORM\Column]
    #[Groups(['user:read', 'user:write', 'base:read'])]
    #[Assert\NotBlank(message: 'At least one role is required')]
    private array $roles = [];

    #[ORM\Column]
    #[Groups(['user:write'])]
    #[Assert\NotBlank(message: 'Password is required')]
    #[Assert\Length(min: 6, minMessage: 'Password must be at least {{ limit }} characters long')]
    private ?string $password = null;

    /**
     * @var Collection<int, Bid>
     */
    #[ORM\OneToMany(targetEntity: Bid::class, mappedBy: 'bidder', orphanRemoval: true)]
    private Collection $bids;

    /**
     * @var Collection<int, AutoBid>
     */
    #[ORM\OneToMany(targetEntity: AutoBid::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $autoBids;

    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?BidConfig $bidConfig = null;

    #[ORM\Column(length: 255)]
    #[Groups(['user:read', 'user:write', 'base:read'])]
    private ?string $name = null;

    public function __construct()
    {
        parent::__construct();
        $this->bids = new ArrayCollection();
        $this->autoBids = new ArrayCollection();
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
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
            $bid->setBidder($this);
        }

        return $this;
    }

    public function removeBid(Bid $bid): static
    {
        if ($this->bids->removeElement($bid)) {
            // set the owning side to null (unless already changed)
            if ($bid->getBidder() === $this) {
                $bid->setBidder(null);
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
            $autoBid->setUser($this);
        }

        return $this;
    }

    public function removeAutoBid(AutoBid $autoBid): static
    {
        if ($this->autoBids->removeElement($autoBid)) {
            // set the owning side to null (unless already changed)
            if ($autoBid->getUser() === $this) {
                $autoBid->setUser(null);
            }
        }

        return $this;
    }

    public function getBidConfig(): ?BidConfig
    {
        return $this->bidConfig;
    }

    public function setBidConfig(BidConfig $bidConfig): static
    {
        // set the owning side of the relation if necessary
        if ($bidConfig->getUser() !== $this) {
            $bidConfig->setUser($this);
        }

        $this->bidConfig = $bidConfig;

        return $this;
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
}
