<?php

namespace App\Event;

use App\Entity\Booking;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched when a provider sends a cost estimation for a visit booking.
 */
class EstimationSentEvent extends Event
{
    public const NAME = 'booking.estimation_sent';

    public function __construct(
        private Booking $booking,
        private string $estimatedCost,
    ) {
    }

    public function getBooking(): Booking
    {
        return $this->booking;
    }

    public function getEstimatedCost(): string
    {
        return $this->estimatedCost;
    }
}
