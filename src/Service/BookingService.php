<?php

namespace App\Service;

use App\Constants\AppConstants;
use App\Entity\Booking;
use App\Entity\Service;
use App\Entity\User;
use App\Event\BookingCompletedEvent;
use App\Event\BookingCreatedEvent;
use App\Event\BookingStatusChangedEvent;
use App\Event\EstimationRespondedEvent;
use App\Event\EstimationSentEvent;
use App\Utility\TrackingIdGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class BookingService
{
    public function __construct(
        private EntityManagerInterface $em,
        private EventDispatcherInterface $dispatcher
    ) {
    }

    public function createBooking(User $customer, Service $service, array $data): Booking
    {
        $booking = new Booking();
        $booking->setCustomer($customer);
        $booking->setService($service);
        $booking->setStatus(AppConstants::BOOKING_STATUS_PENDING);
        $booking->setBookingDate(new \DateTimeImmutable());
        $booking->setBookingType($data['booking_type'] ?? AppConstants::BOOKING_TYPE_ONLINE);
        $booking->setNotes($data['notes'] ?? null);

        // Generate tracking ID
        $trackingId = TrackingIdGenerator::generateWithDate();
        $booking->setTrackingId($trackingId);

        // Set location if provided
        if (!empty($data['latitude']) && !empty($data['longitude'])) {
            $booking->setLatitude($data['latitude']);
            $booking->setLongitude($data['longitude']);
        }

        $this->em->persist($booking);
        $this->em->flush();

        // Dispatch event
        $this->dispatcher->dispatch(new BookingCreatedEvent($booking, $customer));
        $this->em->flush(); // For listeners that modify entities

        return $booking;
    }

    public function updateStatus(Booking $booking, string $newStatus, User $user): void
    {
        $oldStatus = $booking->getStatus();
        $booking->setStatus($newStatus);
        
        $this->em->flush();

        if ($newStatus === 'completed' && $oldStatus !== 'completed') {
            $this->dispatcher->dispatch(new BookingCompletedEvent($booking, $user));
        } else {
            $this->dispatcher->dispatch(new BookingStatusChangedEvent($booking, $oldStatus, $newStatus, $user));
        }

        $this->em->flush();
    }

    public function cancelBooking(Booking $booking, User $user): void
    {
        $oldStatus = $booking->getStatus();
        $booking->setStatus('cancelled');
        $this->em->flush();

        $this->dispatcher->dispatch(new BookingStatusChangedEvent($booking, $oldStatus, 'cancelled', $user));
        $this->em->flush();
    }

    public function submitEstimation(Booking $booking, string $estimatedCost, string $estimationStatus = 'pending'): void
    {
        $booking->setEstimatedCost($estimatedCost);
        $booking->setEstimationStatus($estimationStatus);
        $this->em->flush();

        $this->dispatcher->dispatch(new EstimationSentEvent($booking, $estimatedCost));
        $this->em->flush();
    }

    public function respondToEstimation(Booking $booking, string $response): void
    {
        $booking->setEstimationStatus($response);

        if ($response === 'accepted') {
            $booking->setStatus('confirmed');
        }

        $this->em->flush();

        $this->dispatcher->dispatch(new EstimationRespondedEvent($booking, $response));
        $this->em->flush();
    }

    public function dispatchBooking(Booking $booking, User $user): void
    {
        $oldStatus = $booking->getStatus();
        $booking->setStatus('on-the-way');
        $this->em->flush();

        $this->dispatcher->dispatch(new BookingStatusChangedEvent($booking, $oldStatus, 'on-the-way', $user));
        $this->em->flush();
    }

    public function completeBooking(Booking $booking, User $user): void
    {
        $booking->setStatus('completed');
        $this->em->flush();

        $this->dispatcher->dispatch(new BookingCompletedEvent($booking, $user));
        $this->em->flush();
    }
    
    public function updateLocation(Booking $booking, float $latitude, float $longitude): void
    {
        $booking->setLatitude($latitude);
        $booking->setLongitude($longitude);
        $this->em->flush();
    }
}
