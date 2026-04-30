<?php

namespace App\Entity;

use App\Repository\PreOrderRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PreOrderRepository::class)]
class PreOrder
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $idPreOrder = null;

    #[ORM\Column]
    private ?int $quantity = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\Column]
    private ?\DateTime $preOrderDate = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdPreOrder(): ?int
    {
        return $this->idPreOrder;
    }

    public function setIdPreOrder(int $idPreOrder): static
    {
        $this->idPreOrder = $idPreOrder;

        return $this;
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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
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
}
