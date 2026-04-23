<?php

namespace App\EventListener;

use App\Event\BookingCompletedEvent;
use App\Event\BookingCreatedEvent;
use App\Event\ReviewSubmittedEvent;
use App\Service\GamificationService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Awards reputation points for key user actions:
 * - Booking creation: +10 to customer
 * - Booking completion: +50 to customer, +100 to provider
 * - Review submission: +25 to customer
 */
class GamificationListener
{
    public function __construct(
        private GamificationService $gamification,
    ) {
    }

    #[AsEventListener(event: BookingCreatedEvent::class)]
    public function onBookingCreated(BookingCreatedEvent $event): void
    {
        $this->gamification->awardPoints($event->getCustomer(), 10, false);
    }

    #[AsEventListener(event: BookingCompletedEvent::class)]
    public function onBookingCompleted(BookingCompletedEvent $event): void
    {
        $booking = $event->getBooking();

        // Award customer for completing the booking
        $this->gamification->awardPoints($booking->getCustomer(), 50, false);

        // Award provider for delivering the service
        $this->gamification->awardPoints($booking->getService()->getProvider(), 100, false);
    }

    #[AsEventListener(event: ReviewSubmittedEvent::class)]
    public function onReviewSubmitted(ReviewSubmittedEvent $event): void
    {
        // Award customer for leaving a review
        $this->gamification->awardPoints($event->getReview()->getCustomer(), 25, false);
    }
}
