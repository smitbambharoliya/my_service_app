<?php

namespace App\EventListener;

use App\Event\BookingCompletedEvent;
use App\Event\BookingCreatedEvent;
use App\Event\BookingStatusChangedEvent;
use App\Event\EstimationRespondedEvent;
use App\Event\EstimationSentEvent;
use App\Service\AdminAuditLogger;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Logs all booking-related actions to the admin audit trail.
 */
class BookingAuditListener
{
    public function __construct(
        private AdminAuditLogger $auditLogger,
    ) {
    }

    #[AsEventListener(event: BookingCreatedEvent::class)]
    public function onBookingCreated(BookingCreatedEvent $event): void
    {
        $booking = $event->getBooking();
        $service = $booking->getService();

        $this->auditLogger->logBookingAction('BOOKING_CREATE', $booking->getId(), [
            'customer' => $event->getCustomer()->getEmail(),
            'service' => $service->getTitle(),
            'provider' => $service->getProvider()->getEmail(),
        ]);
    }

    #[AsEventListener(event: BookingStatusChangedEvent::class)]
    public function onBookingStatusChanged(BookingStatusChangedEvent $event): void
    {
        $action = match ($event->getNewStatus()) {
            'on-the-way' => 'BOOKING_DISPATCHED',
            'cancelled' => 'BOOKING_CANCEL',
            default => 'BOOKING_STATUS_UPDATE',
        };

        $this->auditLogger->logBookingAction($action, $event->getBooking()->getId(), [
            'old_status' => $event->getOldStatus(),
            'new_status' => $event->getNewStatus(),
            'updated_by' => $event->getChangedBy()->getEmail(),
        ]);
    }

    #[AsEventListener(event: BookingCompletedEvent::class)]
    public function onBookingCompleted(BookingCompletedEvent $event): void
    {
        $booking = $event->getBooking();

        $this->auditLogger->logBookingAction('BOOKING_COMPLETED', $booking->getId(), [
            'provider' => $event->getCompletedBy()->getEmail(),
            'customer' => $booking->getCustomer()->getEmail(),
        ]);
    }

    #[AsEventListener(event: EstimationSentEvent::class)]
    public function onEstimationSent(EstimationSentEvent $event): void
    {
        $this->auditLogger->logBookingAction('BOOKING_ESTIMATE_SUBMIT', $event->getBooking()->getId(), [
            'estimated_cost' => $event->getEstimatedCost(),
            'status' => 'sent',
        ]);
    }

    #[AsEventListener(event: EstimationRespondedEvent::class)]
    public function onEstimationResponded(EstimationRespondedEvent $event): void
    {
        $action = 'BOOKING_ESTIMATE_' . strtoupper($event->getResponse());

        $this->auditLogger->logBookingAction($action, $event->getBooking()->getId());
    }
}
