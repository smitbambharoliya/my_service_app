<?php

namespace App\Event;

use App\Entity\Booking;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched when a customer accepts or rejects a provider's estimate.
 */
class EstimationRespondedEvent extends Event
{
    public const NAME = 'booking.estimation_responded';

    public function __construct(
        private Booking $booking,
        private string $response, // 'accepted' or 'rejected'
    ) {
    }

    public function getBooking(): Booking
    {
        return $this->booking;
    }

    /**
     * @return string 'accepted' or 'rejected'
     */
    public function getResponse(): string
    {
        return $this->response;
    }
}
