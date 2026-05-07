<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements PasswordAuthenticatedUserInterface, UserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $firstname = null;

    #[ORM\Column(length: 255)]
    private ?string $lastname = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    private ?string $phone = null;

    #[ORM\Column(length: 255)]
    private ?string $address = null;

    #[ORM\Column(length: 255)]
    private ?string $city = null;

    #[ORM\Column(length: 255)]
    private ?string $zipcode = null;

    #[ORM\Column]
    private ?\DateTime $creationDate = null;

    #[ORM\Column]
    private ?bool $actif = null;

    /**
     * @var Collection<int, Availability>
     */
    #[ORM\ManyToMany(targetEntity: Availability::class, inversedBy: 'users')]
    private Collection $Availabilitys;

    #[ORM\ManyToOne(inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Role $userRole = null;

    /**
     * @var Collection<int, Alert>
     */
    #[ORM\OneToMany(targetEntity: Alert::class, mappedBy: 'user')]
    private Collection $alerts;

    /**
     * @var Collection<int, Donation>
     */
    #[ORM\OneToMany(targetEntity: Donation::class, mappedBy: 'user')]
    private Collection $donations;

    /**
     * @var Collection<int, Event>
     */
    #[ORM\OneToMany(targetEntity: Event::class, mappedBy: 'user')]
    private Collection $events;

    /**
     * @var Collection<int, Article>
     */
    #[ORM\OneToMany(targetEntity: Article::class, mappedBy: 'user')]
    private Collection $createArticle;

    /**
     * @var Collection<int, PreOrder>
     */
    #[ORM\OneToMany(targetEntity: PreOrder::class, mappedBy: 'user')]
    private Collection $preOrders;

    public function getRoles(): array
    {
        // Si tu utilises une entité Role, tu peux retourner les rôles sous forme de string
        // Exemple : return [$this->userRole->getName()];
        // Sinon, retourne un rôle par défaut pour l'instant
        return ['ROLE_USER'];
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function eraseCredentials(): void
    {
        // Si tu stockes des données sensibles temporaires, nettoie-les ici
        // Sinon, laisse vide
    }

    public function __construct()
    {
        $this->Availabilitys = new ArrayCollection();
        $this->alerts = new ArrayCollection();
        $this->donations = new ArrayCollection();
        $this->events = new ArrayCollection();
        $this->createArticle = new ArrayCollection();
        $this->preOrders = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getZipcode(): ?string
    {
        return $this->zipcode;
    }

    public function setZipcode(string $zipcode): static
    {
        $this->zipcode = $zipcode;

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

    public function isActif(): ?bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;

        return $this;
    }

    /**
     * @return Collection<int, Availability>
     */
    public function getAvailabilitys(): Collection
    {
        return $this->Availabilitys;
    }

    public function addAvailability(Availability $availability): static
    {
        if (!$this->Availabilitys->contains($availability)) {
            $this->Availabilitys->add($availability);
        }

        return $this;
    }

    public function removeAvailability(Availability $availability): static
    {
        $this->Availabilitys->removeElement($availability);

        return $this;
    }

    public function getUserRole(): ?Role
    {
        return $this->userRole;
    }

    public function setUserRole(?Role $userRole): static
    {
        $this->userRole = $userRole;

        return $this;
    }

    /**
     * @return Collection<int, Alert>
     */
    public function getAlerts(): Collection
    {
        return $this->alerts;
    }

    public function addAlert(Alert $alert): static
    {
        if (!$this->alerts->contains($alert)) {
            $this->alerts->add($alert);
            $alert->setUser($this);
        }

        return $this;
    }

    public function removeAlert(Alert $alert): static
    {
        if ($this->alerts->removeElement($alert)) {
            // set the owning side to null (unless already changed)
            if ($alert->getUser() === $this) {
                $alert->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Donation>
     */
    public function getDonations(): Collection
    {
        return $this->donations;
    }

    public function addDonation(Donation $donation): static
    {
        if (!$this->donations->contains($donation)) {
            $this->donations->add($donation);
            $donation->setUser($this);
        }

        return $this;
    }

    public function removeDonation(Donation $donation): static
    {
        if ($this->donations->removeElement($donation)) {
            // set the owning side to null (unless already changed)
            if ($donation->getUser() === $this) {
                $donation->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Event>
     */
    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function addEvent(Event $event): static
    {
        if (!$this->events->contains($event)) {
            $this->events->add($event);
            $event->setUser($this);
        }

        return $this;
    }

    public function removeEvent(Event $event): static
    {
        if ($this->events->removeElement($event)) {
            // set the owning side to null (unless already changed)
            if ($event->getUser() === $this) {
                $event->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Article>
     */
    public function getCreateArticle(): Collection
    {
        return $this->createArticle;
    }

    public function addCreateArticle(Article $createArticle): static
    {
        if (!$this->createArticle->contains($createArticle)) {
            $this->createArticle->add($createArticle);
            $createArticle->setUser($this);
        }

        return $this;
    }

    public function removeCreateArticle(Article $createArticle): static
    {
        if ($this->createArticle->removeElement($createArticle)) {
            // set the owning side to null (unless already changed)
            if ($createArticle->getUser() === $this) {
                $createArticle->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PreOrder>
     */
    public function getPreOrders(): Collection
    {
        return $this->preOrders;
    }

    public function addPreOrder(PreOrder $preOrder): static
    {
        if (!$this->preOrders->contains($preOrder)) {
            $this->preOrders->add($preOrder);
            $preOrder->setUser($this);
        }

        return $this;
    }

    public function removePreOrder(PreOrder $preOrder): static
    {
        if ($this->preOrders->removeElement($preOrder)) {
            // set the owning side to null (unless already changed)
            if ($preOrder->getUser() === $this) {
                $preOrder->setUser(null);
            }
        }

        return $this;
    }
}
