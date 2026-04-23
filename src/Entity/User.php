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
use Symfony\Component\Validator\Constraints as Assert;

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
    #[Assert\NotBlank(message: 'Email address is required.')]
    #[Assert\Email(message: 'Please enter a valid email address.')]
    #[Assert\Length(max: 180, maxMessage: 'Email must not exceed {{ limit }} characters.')]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column]
    private bool $isVerified = false;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Full name is required.')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'Name must be at least {{ limit }} characters.',
        maxMessage: 'Name must not exceed {{ limit }} characters.'
    )]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z\s\-\']+$/',
        message: 'Name should only contain letters, spaces and hyphens.'
    )]
    private ?string $fullName = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Length(
        min: 10,
        max: 10,
        exactMessage: 'Mobile number must be exactly {{ limit }} digits.'
    )]
    #[Assert\Regex(
        pattern: '/^[0-9]{10}$/',
        message: 'Please enter a valid 10-digit mobile number.'
    )]
    private ?string $mobile = null;

    #[ORM\OneToMany(targetEntity: Service::class, mappedBy: 'provider')]
    private Collection $services;

    #[ORM\OneToMany(targetEntity: Booking::class, mappedBy: 'customer')]
    private Collection $bookings;

    #[ORM\OneToMany(targetEntity: Billing::class, mappedBy: 'user')]
    private Collection $billings;

    #[ORM\OneToMany(targetEntity: Review::class, mappedBy: 'customer')]
    private Collection $reviewsGiven;

    #[ORM\OneToMany(targetEntity: Review::class, mappedBy: 'provider')]
    private Collection $reviewsReceived;

    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $notifications;

    #[ORM\Column(options: ["default" => false])]
    private bool $isPremium = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripeCustomerId = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7, nullable: true)]
    private ?string $latitude = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7, nullable: true)]
    private ?string $longitude = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(length: 40, nullable: true)]
    #[Assert\Length(max: 40, maxMessage: 'City name must not exceed {{ limit }} characters.')]
    private ?string $city = null;

    #[ORM\Column(length: 10, nullable: true)]
    #[Assert\Length(
        min: 6,
        max: 6,
        exactMessage: 'Pincode must be exactly {{ limit }} digits.'
    )]
    #[Assert\Regex(
        pattern: '/^[0-9]{6}$/',
        message: 'Please enter a valid 6-digit pincode.'
    )]
    private ?string $pincode = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Assert\LessThan('today', message: 'Date of birth must be in the past.')]
    private ?\DateTime $dot = null;

    #[ORM\Column(length: 10, nullable: true)]
    #[Assert\Choice(
        choices: ['Male', 'Female', 'Other'],
        message: 'Please select a valid gender.'
    )]
    private ?string $gender = null;

    #[ORM\Column(length: 6, nullable: true)]
    private ?string $otpCode = null;

    #[ORM\Column(type: Types::INTEGER, options: ["default" => 0])]
    private int $otpAttempts = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $otpExpiresAt = null;

    #[ORM\Column(options: ["default" => true])]
    private bool $isActive = true;

    #[ORM\Column(options: ["default" => 0])]
    private int $reputationPoints = 0;

    #[ORM\Column(length: 50, options: ["default" => "Bronze"])]
    private string $tier = 'Bronze';

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(options: ["default" => true])]
    private bool $bookingInAppNotifications = true;

    #[ORM\Column(options: ["default" => true])]
    private bool $bookingEmailNotifications = true;

    #[ORM\Column(options: ["default" => true])]
    private bool $messageInAppNotifications = true;

    #[ORM\Column(options: ["default" => true])]
    private bool $messageEmailNotifications = true;

    public function __construct()
    {
        $this->services = new ArrayCollection();
        $this->bookings = new ArrayCollection();
        $this->billings = new ArrayCollection();
        $this->reviewsGiven = new ArrayCollection();
        $this->reviewsReceived = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
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

    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0" . self::class . "\0password"] = hash('crc32c', $this->password);
        return $data;
    }

    public function eraseCredentials(): void {}

    public function isVerified(): bool
    {
        return $this->isVerified;
    }
    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }
    public function setFullName(string $fullName): static
    {
        $this->fullName = $fullName;
        return $this;
    }

    public function getMobile(): ?string
    {
        return $this->mobile;
    }
    public function setMobile(?string $mobile): static
    {
        $this->mobile = $mobile;
        return $this;
    }

    public function getServices(): Collection
    {
        return $this->services;
    }
    public function getBookings(): Collection
    {
        return $this->bookings;
    }
    public function getBillings(): Collection
    {
        return $this->billings;
    }

    public function getReviewsGiven(): Collection
    {
        return $this->reviewsGiven;
    }
    public function getReviewsReceived(): Collection
    {
        return $this->reviewsReceived;
    }

    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function isPremium(): bool
    {
        return $this->isPremium;
    }
    public function setIsPremium(bool $isPremium): static
    {
        $this->isPremium = $isPremium;
        return $this;
    }

    public function getStripeCustomerId(): ?string
    {
        return $this->stripeCustomerId;
    }
    public function setStripeCustomerId(?string $stripeCustomerId): static
    {
        $this->stripeCustomerId = $stripeCustomerId;
        return $this;
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }
    public function setLatitude(?string $latitude): static
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }
    public function setLongitude(?string $longitude): static
    {
        $this->longitude = $longitude;
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

    public function getPincode(): ?string
    {
        return $this->pincode;
    }
    public function setPincode(?string $pincode): static
    {
        $this->pincode = $pincode;
        return $this;
    }

    public function getDot(): ?\DateTime
    {
        return $this->dot;
    }
    public function setDot(?\DateTime $dot): static
    {
        $this->dot = $dot;
        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }
    public function setGender(?string $gender): static
    {
        $this->gender = $gender;
        return $this;
    }

    public function getOtpCode(): ?string
    {
        return $this->otpCode;
    }
    public function setOtpCode(?string $otpCode): static
    {
        $this->otpCode = $otpCode;
        return $this;
    }

    public function getOtpAttempts(): int
    {
        return $this->otpAttempts;
    }
    public function setOtpAttempts(int $otpAttempts): static
    {
        $this->otpAttempts = $otpAttempts;
        return $this;
    }
    public function incrementOtpAttempts(): static
    {
        $this->otpAttempts++;
        return $this;
    }
    public function resetOtpAttempts(): static
    {
        $this->otpAttempts = 0;
        return $this;
    }

    public function getOtpExpiresAt(): ?\DateTimeImmutable
    {
        return $this->otpExpiresAt;
    }
    public function setOtpExpiresAt(?\DateTimeImmutable $otpExpiresAt): static
    {
        $this->otpExpiresAt = $otpExpiresAt;
        return $this;
    }
    public function isOtpExpired(): bool
    {
        return $this->otpExpiresAt === null || $this->otpExpiresAt < new \DateTimeImmutable();
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }
    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getReputationPoints(): int
    {
        return $this->reputationPoints;
    }

    public function setReputationPoints(int $reputationPoints): static
    {
        $this->reputationPoints = $reputationPoints;
        // Auto rank up
        if ($this->reputationPoints >= 5000) {
            $this->tier = 'Aurora Elite';
        } elseif ($this->reputationPoints >= 2500) {
            $this->tier = 'Platinum';
        } elseif ($this->reputationPoints >= 1000) {
            $this->tier = 'Gold';
        } elseif ($this->reputationPoints >= 500) {
            $this->tier = 'Silver';
        } else {
            $this->tier = 'Bronze';
        }

        return $this;
    }

    public function getTier(): string
    {
        return $this->tier;
    }

    public function setTier(string $tier): static
    {
        $this->tier = $tier;
        return $this;
    }

    public function getTierColor(): string
    {
        return match ($this->tier) {
            'Aurora Elite' => 'var(--aurora-cyan)',
            'Platinum'     => 'var(--aurora-purple)',
            'Gold'         => 'var(--aurora-amber)',
            'Silver'       => '#c0c0c0',
            default        => '#cd7f32', // Bronze
        };
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function isBookingInAppNotifications(): bool
    {
        return $this->bookingInAppNotifications;
    }

    public function setBookingInAppNotifications(bool $bookingInAppNotifications): static
    {
        $this->bookingInAppNotifications = $bookingInAppNotifications;

        return $this;
    }

    public function isBookingEmailNotifications(): bool
    {
        return $this->bookingEmailNotifications;
    }

    public function setBookingEmailNotifications(bool $bookingEmailNotifications): static
    {
        $this->bookingEmailNotifications = $bookingEmailNotifications;

        return $this;
    }

    public function isMessageInAppNotifications(): bool
    {
        return $this->messageInAppNotifications;
    }

    public function setMessageInAppNotifications(bool $messageInAppNotifications): static
    {
        $this->messageInAppNotifications = $messageInAppNotifications;

        return $this;
    }

    public function isMessageEmailNotifications(): bool
    {
        return $this->messageEmailNotifications;
    }

    public function setMessageEmailNotifications(bool $messageEmailNotifications): static
    {
        $this->messageEmailNotifications = $messageEmailNotifications;

        return $this;
    }
}
