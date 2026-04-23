<?php

namespace App\EventListener;

use App\Event\ReviewSubmittedEvent;
use App\Service\NotificationService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Notifies the service provider when they receive a new customer review.
 */
#[AsEventListener(event: ReviewSubmittedEvent::class)]
class ReviewNotificationListener
{
    public function __construct(
        private NotificationService $notificationService,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(ReviewSubmittedEvent $event): void
    {
        $review = $event->getReview();
        $provider = $review->getProvider();
        $customer = $review->getCustomer();
        $booking = $review->getBooking();

        $this->notificationService->notifyBookingUpdate(
            $provider,
            'New review received',
            sprintf(
                '%s left a %d-star review for "%s".',
                $customer->getFullName(),
                $review->getRating(),
                $booking->getService()->getTitle()
            ),
            $this->urlGenerator->generate('app_booking_detail', ['id' => $booking->getId()])
        );
    }
}
