<?php

namespace App\Entity;

use App\Enum\PreOrderStatus;
use App\Repository\PreOrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PreOrderRepository::class)]
class PreOrder
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $quantity = null;

    #[ORM\Column(enumType: PreOrderStatus::class)]
    private PreOrderStatus $status = PreOrderStatus::EN_ATTENTE;

    #[ORM\Column]
    private ?\DateTime $preOrderDate = null;

    /**
     * @var Collection<int, Article>
     */
    #[ORM\ManyToMany(targetEntity: Article::class, inversedBy: 'preOrders')]
    private Collection $articles;

    #[ORM\ManyToOne(inversedBy: 'preOrders')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    public function __construct()
    {
        $this->articles = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getStatus(): PreOrderStatus
    {
        return $this->status;
    }

    public function setStatus(PreOrderStatus $status): void
    {
        $this->status = $status;
    }

    public function getPreOrderDate(): ?\DateTime
    {
        return $this->preOrderDate;
    }

    public function setPreOrderDate(\DateTime $preOrderDate): static
    {
        $this->preOrderDate = $preOrderDate;

        return $this;
    }

    /**
     * @return Collection<int, Article>
     */
    public function getArticles(): Collection
    {
        return $this->articles;
    }

    public function addArticle(Article $article): static
    {
        if (!$this->articles->contains($article)) {
            $this->articles->add($article);
        }

        return $this;
    }

    public function removeArticle(Article $article): static
    {
        $this->articles->removeElement($article);

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
