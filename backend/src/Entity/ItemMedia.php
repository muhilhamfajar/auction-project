<?php

namespace App\Entity;

use App\Repository\ItemMediaRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ItemMediaRepository::class)]
#[Vich\Uploadable]
class ItemMedia extends BaseEntity
{
    #[ORM\ManyToOne(inversedBy: 'medias')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['item_media:read', 'item_media:write'])]
    private ?Item $item = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['item_media:read'])]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['item_media:read', 'item_media:write'])]
    private ?string $caption = null;

    #[Vich\UploadableField(mapping: 'item_pictures', fileNameProperty: 'name')]
    #[Groups(['item_media:write'])]
    private ?File $imageFile = null;

    public function __construct()
    {
        parent::__construct();
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getCaption(): ?string
    {
        return $this->caption;
    }

    public function setCaption(?string $caption): static
    {
        $this->caption = $caption;

        return $this;
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function setImageFile(?File $imageFile): self
    {
        $this->imageFile = $imageFile;

        if (null !== $imageFile) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTimeImmutable();
        }

        return $this;
    }
}
