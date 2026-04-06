<?php

namespace App\Entity;

use App\Repository\BookingRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BookingRepository::class)]
class Booking
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $customer = null;

    #[ORM\ManyToOne(inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Service $service = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\Column(length: 10, options: ["default" => "online"])]
    private string $bookingType = 'online';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $estimatedCost = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $estimationStatus = null;

    #[ORM\OneToOne(mappedBy: 'booking', cascade: ['persist', 'remove'])]
    private ?Review $review = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $bookingDate = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 7, nullable: true)]
    private ?string $latitude = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 7, nullable: true)]
    private ?string $longitude = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $trackingId = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomer(): ?User
    {
        return $this->customer;
    }

    public function setCustomer(?User $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    public function getService(): ?Service
    {
        return $this->service;
    }

    public function setService(?Service $service): static
    {
        $this->service = $service;

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

    public function getBookingDate(): ?\DateTimeImmutable { return $this->bookingDate; }
    public function setBookingDate(\DateTimeImmutable $bookingDate): static { $this->bookingDate = $bookingDate; return $this; }

    public function getBookingType(): string { return $this->bookingType; }
    public function setBookingType(string $bookingType): static { $this->bookingType = $bookingType; return $this; }

    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $notes): static { $this->notes = $notes; return $this; }

    public function getEstimatedCost(): ?string { return $this->estimatedCost; }
    public function setEstimatedCost(?string $estimatedCost): static { $this->estimatedCost = $estimatedCost; return $this; }

    public function getEstimationStatus(): ?string { return $this->estimationStatus; }
    public function setEstimationStatus(?string $estimationStatus): static { $this->estimationStatus = $estimationStatus; return $this; }

    public function getReview(): ?Review { return $this->review; }
    public function setReview(?Review $review): static
    {
        // unset the owning side of the relation if necessary
        if ($review === null && $this->review !== null) {
            $this->review->setBooking(null);
        }

        // set the owning side of the relation if necessary
        if ($review !== null && $review->getBooking() !== $this) {
            $review->setBooking($this);
        }

        $this->review = $review;

        return $this;
    }

    public function getLatitude(): ?string { return $this->latitude; }
    public function setLatitude(?string $latitude): static { $this->latitude = $latitude; return $this; }

    public function getLongitude(): ?string { return $this->longitude; }
    public function setLongitude(?string $longitude): static { $this->longitude = $longitude; return $this; }

    public function getTrackingId(): ?string { return $this->trackingId; }
    public function setTrackingId(?string $trackingId): static { $this->trackingId = $trackingId; return $this; }
}
