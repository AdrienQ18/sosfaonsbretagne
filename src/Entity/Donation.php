<?php

namespace App\Entity;

use App\Enum\DonationStatus;
use App\Repository\DonationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DonationRepository::class)]
class Donation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $amount = null;

    #[ORM\Column]
    private ?\DateTime $donationDate = null;

    #[ORM\ManyToOne(inversedBy: 'donations')]
    private ?User $user = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lastname = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $firstname = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $zipcode = null;

    #[ORM\Column(enumType: DonationStatus::class)]
    private ?DonationStatus $status = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $receiptPdfPath = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fiscalReceiptNumber = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $receiptGeneratedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): ?DonationStatus
    {
        return $this->status;
    }

    public function setStatus(?DonationStatus $status): void
    {
        $this->status = $status;
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

    public function getDonationDate(): ?\DateTime
    {
        return $this->donationDate;
    }

    public function setDonationDate(\DateTime $donationDate): static
    {
        $this->donationDate = $donationDate;

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

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(?string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(?string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getZipcode(): ?string
    {
        return $this->zipcode;
    }

    public function setZipcode(?string $zipcode): static
    {
        $this->zipcode = $zipcode;

        return $this;
    }

    public function getReceiptPdfPath(): ?string
    {
        return $this->receiptPdfPath;
    }

    public function setReceiptPdfPath(?string $receiptPdfPath): static
    {
        $this->receiptPdfPath = $receiptPdfPath;

        return $this;
    }

    public function getFiscalReceiptNumber(): ?string
    {
        return $this->fiscalReceiptNumber;
    }

    public function setFiscalReceiptNumber(?string $fiscalReceiptNumber): static
    {
        $this->fiscalReceiptNumber = $fiscalReceiptNumber;

        return $this;
    }

    public function getReceiptGeneratedAt(): ?\DateTimeImmutable
    {
        return $this->receiptGenerateAt;
    }

    public function setReceiptGeneratedAt(?\DateTimeImmutable $receiptGenerateAt): static
    {
        $this->receiptGenerateAt = $receiptGenerateAt;

        return $this;
    }
}
