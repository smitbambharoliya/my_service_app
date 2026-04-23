<?php

namespace App\Event;

use App\Entity\Review;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched when a customer submits a review for a completed booking.
 */
class ReviewSubmittedEvent extends Event
{
    public const NAME = 'review.submitted';

    public function __construct(
        private Review $review,
    ) {
    }

    public function getReview(): Review
    {
        return $this->review;
    }
}
