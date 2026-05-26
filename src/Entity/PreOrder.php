<?php

namespace App\Entity;

use App\Enum\PreOrderStatus;
use App\Repository\PreOrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity(repositoryClass: PreOrderRepository::class)]
class PreOrder
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(enumType: PreOrderStatus::class)]
    private PreOrderStatus $status = PreOrderStatus::EN_ATTENTE;

    #[ORM\Column]
    private ?\DateTimeImmutable $preOrderDate = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $validatedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $paidAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $helloassoOrderId = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $totalAmount = null;

    #[ORM\ManyToOne(inversedBy: 'preOrders')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    /**
     * @var Collection<int, PreOrderItem>
     */
    #[ORM\OneToMany(
        targetEntity: PreOrderItem::class,
        mappedBy: 'preOrder',
        orphanRemoval: true,
        cascade: ['persist']
    )]
    private Collection $preOrderItems;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $invoicePdfPath = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $invoiceReceiptNumber = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $invoiceGeneratedAt = null;

    public function __construct()
    {
        $this->preOrderItems = new ArrayCollection();
        $this->preOrderDate = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getStatus(): PreOrderStatus
    {
        return $this->status;
    }

    public function setStatus(PreOrderStatus $status): void
    {
        $this->status = $status;
    }

    public function getPreOrderDate(): ?\DateTimeImmutable
    {
        return $this->preOrderDate;
    }

    public function setPreOrderDate(?\DateTimeImmutable $preOrderDate): void
    {
        $this->preOrderDate = $preOrderDate;
    }

    public function getValidatedAt(): ?\DateTimeImmutable
    {
        return $this->validatedAt;
    }

    public function setValidatedAt(?\DateTimeImmutable $validatedAt): void
    {
        $this->validatedAt = $validatedAt;
    }

    public function getPaidAt(): ?\DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function setPaidAt(?\DateTimeImmutable $paidAt): void
    {
        $this->paidAt = $paidAt;
    }

    public function getHelloassoOrderId(): ?string
    {
        return $this->helloassoOrderId;
    }

    public function setHelloassoOrderId(?string $helloassoOrderId): void
    {
        $this->helloassoOrderId = $helloassoOrderId;
    }

    public function getTotalAmount(): ?string
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(?string $totalAmount): void
    {
        $this->totalAmount = $totalAmount;
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
            $preOrderItem->setPreOrder($this);
        }

        return $this;
    }

    public function removePreOrderItem(PreOrderItem $preOrderItem): static
    {
        if ($this->preOrderItems->removeElement($preOrderItem)) {
            if ($preOrderItem->getPreOrder() === $this) {
                $preOrderItem->setPreOrder(null);
            }
        }

        return $this;
    }

    public function getInvoicePdfPath(): ?string
    {
        return $this->invoicePdfPath;
    }

    public function setInvoicePdfPath(?string $invoicePdfPath): static
    {
        $this->invoicePdfPath = $invoicePdfPath;

        return $this;
    }

    public function getInvoiceReceiptNumber(): ?string
    {
        return $this->invoiceReceiptNumber;
    }

    public function setInvoiceReceiptNumber(?string $invoiceReceiptNumber): static
    {
        $this->invoiceReceiptNumber = $invoiceReceiptNumber;

        return $this;
    }

    public function getInvoiceGeneratedAt(): ?\DateTimeImmutable
    {
        return $this->invoiceGeneratedAt;
    }

    public function setInvoiceGeneratedAt(?\DateTimeImmutable $invoiceGeneratedAt): static
    {
        $this->invoiceGeneratedAt = $invoiceGeneratedAt;

        return $this;
    }

}


