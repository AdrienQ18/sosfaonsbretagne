<?php

namespace App\Entity;

use App\Enum\BirdhouseDiameter;
use App\Repository\PreOrderItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PreOrderItemRepository::class)]
class PreOrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $quantity = null;

    #[ORM\Column(nullable: true, enumType: BirdhouseDiameter::class)]
    private ?BirdhouseDiameter $diameter = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $unitPrice = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $totalPrice = null;

    #[ORM\ManyToOne(inversedBy: 'preOrderItems')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Article $article = null;

    #[ORM\ManyToOne(inversedBy: 'preOrderItems')]
    #[ORM\JoinColumn(nullable: false)]
    private ?PreOrder $preOrder = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(?int $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function getDiameter(): ?BirdhouseDiameter
    {
        return $this->diameter;
    }

    public function setDiameter(?BirdhouseDiameter $diameter): void
    {
        $this->diameter = $diameter;
    }

    public function getUnitPrice(): ?string
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(?string $unitPrice): void
    {
        $this->unitPrice = $unitPrice;
    }

    public function getTotalPrice(): ?string
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(?string $totalPrice): void
    {
        $this->totalPrice = $totalPrice;
    }

    public function getArticle(): ?Article
    {
        return $this->article;
    }

    public function setArticle(?Article $article): void
    {
        $this->article = $article;
    }

    public function getPreOrder(): ?PreOrder
    {
        return $this->preOrder;
    }

    public function setPreOrder(?PreOrder $preOrder): void
    {
        $this->preOrder = $preOrder;
    }


}
