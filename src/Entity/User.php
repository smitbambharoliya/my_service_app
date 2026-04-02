<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column]
    private bool $isVerified = false;

    #[ORM\Column(length: 255)]
    private ?string $fullName = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $mobile = null;

    #[ORM\OneToMany(targetEntity: Service::class, mappedBy: 'provider')]
    private Collection $services;

    #[ORM\OneToMany(targetEntity: Booking::class, mappedBy: 'customer')]
    private Collection $bookings;

    #[ORM\OneToMany(targetEntity: Billing::class, mappedBy: 'user')]
    private Collection $billings;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(length: 40, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $pincode = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $dot = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $gender = null;

    public function __construct()
    {
        $this->services = new ArrayCollection();
        $this->bookings = new ArrayCollection();
        $this->billings = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): static { $this->email = $email; return $this; }

    public function getUserIdentifier(): string { return (string) $this->email; }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static { $this->roles = $roles; return $this; }

    public function getPassword(): ?string { return $this->password; }
    public function setPassword(string $password): static { $this->password = $password; return $this; }

    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);
        return $data;
    }

    public function eraseCredentials(): void {}

    public function isVerified(): bool { return $this->isVerified; }
    public function setIsVerified(bool $isVerified): static { $this->isVerified = $isVerified; return $this; }

    public function getFullName(): ?string { return $this->fullName; }
    public function setFullName(string $fullName): static { $this->fullName = $fullName; return $this; }

    public function getMobile(): ?string { return $this->mobile; }
    public function setMobile(?string $mobile): static { $this->mobile = $mobile; return $this; }

    public function getServices(): Collection { return $this->services; }
    public function getBookings(): Collection { return $this->bookings; }
    public function getBillings(): Collection { return $this->billings; }

    public function getAddress(): ?string { return $this->address; }
    public function setAddress(?string $address): static { $this->address = $address; return $this; }

    public function getCity(): ?string { return $this->city; }
    public function setCity(?string $city): static { $this->city = $city; return $this; }

    public function getPincode(): ?string { return $this->pincode; }
    public function setPincode(?string $pincode): static { $this->pincode = $pincode; return $this; }

    public function getDot(): ?\DateTime { return $this->dot; }
    public function setDot(?\DateTime $dot): static { $this->dot = $dot; return $this; }

    public function getGender(): ?string { return $this->gender; }
    public function setGender(?string $gender): static { $this->gender = $gender; return $this; }
}