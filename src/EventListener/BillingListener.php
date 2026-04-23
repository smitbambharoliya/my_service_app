<?php

namespace App\EventListener;

use App\Entity\Billing;
use App\Event\BookingCompletedEvent;
use App\Event\EstimationSentEvent;
use App\Event\PaymentCompletedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Handles automatic billing generation for:
 * - Estimates sent by providers (creates estimate bill)
 * - Completed bookings (creates final bill)
 * - Confirmed payments (applies premium status if subscription)
 */
class BillingListener
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    #[AsEventListener(event: EstimationSentEvent::class)]
    public function onEstimationSent(EstimationSentEvent $event): void
    {
        $booking = $event->getBooking();

        $billing = new Billing();
        $billing->setUser($booking->getCustomer());
        $billing->setAmount($event->getEstimatedCost());
        $billing->setPaymentStatus('estimate');
        $billing->setTransactionId('EST-' . strtoupper(substr(uniqid(), -6)));
        $billing->setCreatedAt(new \DateTimeImmutable());
        $billing->setCategory($booking->getService()->getCategory()->getName());
        $billing->setServiceName($booking->getService()->getTitle());
        $billing->setDescription('Estimated cost provided by the professional for the visit.');

        $this->entityManager->persist($billing);
        // Flush deferred — will be flushed by the controller after all listeners complete
    }

    #[AsEventListener(event: BookingCompletedEvent::class)]
    public function onBookingCompleted(BookingCompletedEvent $event): void
    {
        $booking = $event->getBooking();

        $billing = new Billing();
        $billing->setUser($booking->getCustomer());

        if ($booking->getBookingType() === 'visit') {
            $billing->setAmount($booking->getEstimatedCost());
        } else {
            $billing->setAmount((string) $booking->getService()->getPrice());
        }

        $billing->setPaymentStatus('unpaid');
        $billing->setTransactionId('BILL-' . strtoupper(substr(uniqid(), -6)));
        $billing->setCreatedAt(new \DateTimeImmutable());
        $billing->setCategory($booking->getService()->getCategory()->getName());
        $billing->setServiceName($booking->getService()->getTitle());
        $billing->setDescription('Final bill for completed service request.');

        $this->entityManager->persist($billing);
        // Flush deferred — will be flushed by the controller
    }

    #[AsEventListener(event: PaymentCompletedEvent::class)]
    public function onPaymentCompleted(PaymentCompletedEvent $event): void
    {
        $billing = $event->getBilling();

        // If this was a subscription upgrade, apply premium status to all provider services
        if ($billing->getCategory() === 'Subscription') {
            $services = $billing->getUser()->getServices();
            foreach ($services as $service) {
                $service->setIsPremium(true);
            }
        }
        // Flush deferred — will be flushed by the controller
    }
}
