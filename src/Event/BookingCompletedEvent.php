<?php

namespace App\Event;

use App\Entity\Booking;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched when a booking is marked as completed.
 * Triggers gamification, billing, notification, and audit side-effects.
 */
class BookingCompletedEvent extends Event
{
    public const NAME = 'booking.completed';

    public function __construct(
        private Booking $booking,
        private User $completedBy,
    ) {
    }

    public function getBooking(): Booking
    {
        return $this->booking;
    }

    public function getCompletedBy(): User
    {
        return $this->completedBy;
    }
}
