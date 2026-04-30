<?php

namespace App\Entity;

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

    #[ORM\Column]
    private ?int $idDonation = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $amount = null;

    #[ORM\Column]
    private ?\DateTime $donationDate = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdDonation(): ?int
    {
        return $this->idDonation;
    }

    public function setIdDonation(int $idDonation): static
    {
        $this->idDonation = $idDonation;

        return $this;
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
}
