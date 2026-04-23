<?php

namespace App\Event;

use App\Entity\Booking;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched when a new booking is created by a customer.
 */
class BookingCreatedEvent extends Event
{
    public const NAME = 'booking.created';

    public function __construct(
        private Booking $booking,
        private User $customer,
    ) {
    }

    public function getBooking(): Booking
    {
        return $this->booking;
    }

    public function getCustomer(): User
    {
        return $this->customer;
    }
}
