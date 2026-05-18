<?php

namespace App\Entity;

use App\Enum\DonationStatus;
use App\Enum\DonorType;
use App\Repository\DonationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DonationRepository::class)]
#[ORM\Index(name: 'idx_donation_date', columns: ['donation_date'])]
#[ORM\Index(name: 'idx_donation_status', columns: ['status'])]
#[ORM\Index(name: 'idx_donor_type', columns: ['donor_type'])]
#[ORM\Index(name: 'idx_donation_email', columns: ['email'])]
#[ORM\Index(name: 'idx_company_siret', columns: ['company_siret'])]
#[ORM\Index(name: 'idx_status_date', columns: ['status', 'donation_date'])]
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

    #[ORM\Column(length: 50, enumType: DonationStatus::class)]
    private ?DonationStatus $status = null;

    #[ORM\Column(length: 50, enumType: DonorType::class)]
    private ?DonorType $donorType = null;
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $receiptPdfPath = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fiscalReceiptNumber = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $receiptGeneratedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $companyName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $companySiret = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(?string $amount): void
    {
        $this->amount = $amount;
    }

    public function getDonationDate(): ?\DateTime
    {
        return $this->donationDate;
    }

    public function setDonationDate(?\DateTime $donationDate): void
    {
        $this->donationDate = $donationDate;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(?string $lastname): void
    {
        $this->lastname = $lastname;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(?string $firstname): void
    {
        $this->firstname = $firstname;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): void
    {
        $this->address = $address;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): void
    {
        $this->city = $city;
    }

    public function getZipcode(): ?string
    {
        return $this->zipcode;
    }

    public function setZipcode(?string $zipcode): void
    {
        $this->zipcode = $zipcode;
    }

    public function getStatus(): ?DonationStatus
    {
        return $this->status;
    }

    public function setStatus(?DonationStatus $status): void
    {
        $this->status = $status;
    }

    public function getDonorType(): ?DonorType
    {
        return $this->donorType;
    }

    public function setDonorType(?DonorType $donorType): void
    {
        $this->donorType = $donorType;
    }

    public function getReceiptPdfPath(): ?string
    {
        return $this->receiptPdfPath;
    }

    public function setReceiptPdfPath(?string $receiptPdfPath): void
    {
        $this->receiptPdfPath = $receiptPdfPath;
    }

    public function getFiscalReceiptNumber(): ?string
    {
        return $this->fiscalReceiptNumber;
    }

    public function setFiscalReceiptNumber(?string $fiscalReceiptNumber): void
    {
        $this->fiscalReceiptNumber = $fiscalReceiptNumber;
    }

    public function getReceiptGeneratedAt(): ?\DateTimeImmutable
    {
        return $this->receiptGeneratedAt;
    }

    public function setReceiptGeneratedAt(?\DateTimeImmutable $receiptGeneratedAt): void
    {
        $this->receiptGeneratedAt = $receiptGeneratedAt;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(?string $companyName): void
    {
        $this->companyName = $companyName;
    }

    public function getCompanySiret(): ?string
    {
        return $this->companySiret;
    }

    public function setCompanySiret(?string $companySiret): void
    {
        $this->companySiret = $companySiret;
    }


}
