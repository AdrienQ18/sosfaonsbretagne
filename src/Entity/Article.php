<?php

namespace App\Entity;

use App\Repository\ArticleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArticleRepository::class)]
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $price = null;

    #[ORM\Column(length: 255)]
    private ?string $image = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $creationDate = null;

    #[ORM\ManyToOne(inversedBy: 'createArticle')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    /**
     * @var Collection<int, PreOrderItem>
     */
    #[ORM\OneToMany(
        targetEntity: PreOrderItem::class,
        mappedBy: 'article',
        orphanRemoval: true
    )]
    private Collection $preOrderItems;

    public function __construct()
    {
        $this->preOrderItems = new ArrayCollection();
        $this->creationDate = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(?string $price): void
    {
        $this->price = $price;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): void
    {
        $this->image = $image;
    }

    public function getCreationDate(): ?\DateTimeImmutable
    {
        return $this->creationDate;
    }

    public function setCreationDate(?\DateTimeImmutable $creationDate): void
    {
        $this->creationDate = $creationDate;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function getPreOrderItems(): Collection
    {
        return $this->preOrderItems;
    }

    public function addPreOrderItem(PreOrderItem $preOrderItem): static
    {
        if (!$this->preOrderItems->contains($preOrderItem)) {
            $this->preOrderItems->add($preOrderItem);
            $preOrderItem->setArticle($this);
        }

        return $this;
    }

    public function removePreOrderItem(PreOrderItem $preOrderItem): static
    {
        if ($this->preOrderItems->removeElement($preOrderItem)) {
            if ($preOrderItem->getArticle() === $this) {
                $preOrderItem->setArticle(null);
            }
        }

        return $this;
    }
}
