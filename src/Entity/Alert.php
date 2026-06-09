<?php

namespace App\Entity;

use App\Enum\AlertStatus;
use App\Repository\AlertRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AlertRepository::class)]
class Alert
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    #[ORM\Column(nullable: true)]
    private ?int $surface = null;

    #[ORM\Column]
    private ?\DateTime $creationDate = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $interventionDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $localisation = null;

    #[ORM\Column(enumType: AlertStatus::class)]
    private AlertStatus $status = AlertStatus::SIGNALEMENT_PASSEE;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $gpsLatitude = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $gpsLongitude = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\ManyToOne(inversedBy: 'alerts')]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    private ?string $cultureType = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getSurface(): ?int
    {
        return $this->surface;
    }

    public function setSurface(?int $surface): static
    {
        $this->surface = $surface;

        return $this;
    }

    public function getCreationDate(): ?\DateTime
    {
        return $this->creationDate;
    }

    public function setCreationDate(\DateTime $creationDate): static
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    public function getInterventionDate(): ?\DateTime
    {
        return $this->interventionDate;
    }

    public function setInterventionDate(?\DateTime $interventionDate): static
    {
        $this->interventionDate = $interventionDate;

        return $this;
    }

    public function getLocalisation(): ?string
    {
        return $this->localisation;
    }

    public function setLocalisation(?string $localisation): static
    {
        $this->localisation = $localisation;

        return $this;
    }

    public function getStatus(): AlertStatus
    {
        return $this->status;
    }

    public function setStatus(AlertStatus $status): void
    {
        $this->status = $status;
    }

    public function getGpsLatitude(): ?string
    {
        return $this->gpsLatitude;
    }

    public function setGpsLatitude(?string $gpsLatitude): static
    {
        $this->gpsLatitude = $gpsLatitude;

        return $this;
    }

    public function getGpsLongitude(): ?string
    {
        return $this->gpsLongitude;
    }

    public function setGpsLongitude(?string $gpsLongitude): static
    {
        $this->gpsLongitude = $gpsLongitude;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

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

    public function getCultureType(): ?string
    {
        return $this->cultureType;
    }

    public function setCultureType(string $cultureType): static
    {
        $this->cultureType = $cultureType;

        return $this;
    }

}
