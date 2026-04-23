<?php

namespace App\EventListener;

use App\Event\BookingCompletedEvent;
use App\Event\BookingCreatedEvent;
use App\Event\BookingStatusChangedEvent;
use App\Event\EstimationRespondedEvent;
use App\Event\EstimationSentEvent;
use App\Service\NotificationService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Handles all booking-related notifications (in-app + email)
 * for both customer and provider.
 */
class BookingNotificationListener
{
    public function __construct(
        private NotificationService $notificationService,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[AsEventListener(event: BookingCreatedEvent::class)]
    public function onBookingCreated(BookingCreatedEvent $event): void
    {
        $booking = $event->getBooking();
        $service = $booking->getService();
        $provider = $service->getProvider();
        $customer = $event->getCustomer();
        $bookingUrl = $this->urlGenerator->generate('app_booking_detail', ['id' => $booking->getId()]);

        // Notify provider about new booking request
        $this->notificationService->notifyBookingUpdate(
            $provider,
            'New booking request received',
            sprintf(
                '%s requested "%s". Review the booking details and respond from your dashboard.',
                $customer->getFullName(),
                $service->getTitle()
            ),
            $bookingUrl
        );

        // Notify customer about their booking creation
        $this->notificationService->notifyBookingUpdate(
            $customer,
            'Booking request created',
            sprintf(
                'Your booking for "%s" is now pending provider confirmation.',
                $service->getTitle()
            ),
            $bookingUrl
        );
    }

    #[AsEventListener(event: BookingStatusChangedEvent::class)]
    public function onBookingStatusChanged(BookingStatusChangedEvent $event): void
    {
        $booking = $event->getBooking();
        $service = $booking->getService();
        $bookingUrl = $this->urlGenerator->generate('app_booking_detail', ['id' => $booking->getId()]);

        $oldStatus = $event->getOldStatus();
        $newStatus = $event->getNewStatus();

        // Notify customer about status change
        $this->notificationService->notifyBookingUpdate(
            $booking->getCustomer(),
            'Booking status updated',
            sprintf(
                'Your booking for "%s" moved from %s to %s.',
                $service->getTitle(),
                str_replace('-', ' ', $oldStatus),
                str_replace('-', ' ', $newStatus)
            ),
            $bookingUrl
        );

        // Special message for dispatch
        if ($newStatus === 'on-the-way') {
            $this->notificationService->notifyBookingUpdate(
                $booking->getCustomer(),
                'Provider dispatched',
                sprintf(
                    'Your provider is on the way for "%s".',
                    $service->getTitle()
                ),
                $bookingUrl
            );
        }

        // Notify provider if customer cancelled
        if ($newStatus === 'cancelled') {
            $this->notificationService->notifyBookingUpdate(
                $service->getProvider(),
                'Booking cancelled',
                sprintf(
                    '%s cancelled the booking for "%s".',
                    $booking->getCustomer()->getFullName(),
                    $service->getTitle()
                ),
                $bookingUrl
            );
        }
    }

    #[AsEventListener(event: BookingCompletedEvent::class)]
    public function onBookingCompleted(BookingCompletedEvent $event): void
    {
        $booking = $event->getBooking();
        $service = $booking->getService();
        $bookingUrl = $this->urlGenerator->generate('app_booking_detail', ['id' => $booking->getId()]);

        $this->notificationService->notifyBookingUpdate(
            $booking->getCustomer(),
            'Booking completed',
            sprintf(
                'Your booking for "%s" has been marked completed.',
                $service->getTitle()
            ),
            $bookingUrl
        );
    }

    #[AsEventListener(event: EstimationSentEvent::class)]
    public function onEstimationSent(EstimationSentEvent $event): void
    {
        $booking = $event->getBooking();
        $service = $booking->getService();
        $bookingUrl = $this->urlGenerator->generate('app_booking_detail', ['id' => $booking->getId()]);

        $this->notificationService->notifyBookingUpdate(
            $booking->getCustomer(),
            'New estimate received',
            sprintf(
                'A new estimate of Rs. %s was shared for "%s".',
                $event->getEstimatedCost(),
                $service->getTitle()
            ),
            $bookingUrl
        );
    }

    #[AsEventListener(event: EstimationRespondedEvent::class)]
    public function onEstimationResponded(EstimationRespondedEvent $event): void
    {
        $booking = $event->getBooking();
        $service = $booking->getService();
        $bookingUrl = $this->urlGenerator->generate('app_booking_detail', ['id' => $booking->getId()]);

        $this->notificationService->notifyBookingUpdate(
            $service->getProvider(),
            'Estimate response received',
            sprintf(
                '%s %s the estimate for "%s".',
                $booking->getCustomer()->getFullName(),
                $event->getResponse(),
                $service->getTitle()
            ),
            $bookingUrl
        );
    }
}
