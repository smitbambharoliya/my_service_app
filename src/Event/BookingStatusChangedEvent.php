<?php

namespace App\Event;

use App\Entity\Booking;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched when a booking's status transitions (e.g. pending → confirmed → on-the-way).
 * NOT dispatched for the final "completed" transition — use BookingCompletedEvent instead.
 */
class BookingStatusChangedEvent extends Event
{
    public const NAME = 'booking.status_changed';

    public function __construct(
        private Booking $booking,
        private string $oldStatus,
        private string $newStatus,
        private User $changedBy,
    ) {
    }

    public function getBooking(): Booking
    {
        return $this->booking;
    }

    public function getOldStatus(): string
    {
        return $this->oldStatus;
    }

    public function getNewStatus(): string
    {
        return $this->newStatus;
    }

    public function getChangedBy(): User
    {
        return $this->changedBy;
    }
}
