<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\Service;
use App\Entity\User;
use App\Event\BookingCompletedEvent;
use App\Event\BookingCreatedEvent;
use App\Event\BookingStatusChangedEvent;
use App\Event\EstimationRespondedEvent;
use App\Event\EstimationSentEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Route('/booking')]
class BookingController extends AbstractController
{
    public function __construct(
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    /**
     * Create a new booking
     */
    #[Route('/create/{id}', name: 'app_booking_create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function createBooking(Service $service, Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Validate CSRF token
        if (!$this->isCsrfTokenValid('booking_create_' . $service->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token. Please try again.');
            return $this->redirectToRoute('app_service_show', ['id' => $service->getId()]);
        }

        // Check if user is trying to book their own service
        if ($service->getProvider() === $user) {
            $this->addFlash('error', 'You cannot book your own service.');
            return $this->redirectToRoute('app_service_show', ['id' => $service->getId()]);
        }

        $booking = new Booking();
        $booking->setCustomer($user);
        $booking->setService($service);
        $booking->setStatus('pending');
        $booking->setBookingDate(new \DateTimeImmutable());
        $booking->setBookingType($request->request->get('booking_type', 'online'));
        $booking->setNotes($request->request->get('notes'));

        // Generate tracking ID
        $trackingId = 'TRK-' . strtoupper(uniqid());
        $booking->setTrackingId($trackingId);

        // Set location if provided
        $latitude = $request->request->get('latitude');
        $longitude = $request->request->get('longitude');
        if ($latitude && $longitude) {
            $booking->setLatitude($latitude);
            $booking->setLongitude($longitude);
        }

        $em->persist($booking);
        $em->flush();

        // Dispatch event — listeners handle notifications, audit, gamification
        $this->dispatcher->dispatch(new BookingCreatedEvent($booking, $user));
        $em->flush();

        $this->addFlash('success', 'Booking created successfully! Tracking ID: ' . $trackingId);
        return $this->redirectToRoute('app_booking_detail', ['id' => $booking->getId()]);
    }

    /**
     * View booking details
     */
    #[Route('/{id}', name: 'app_booking_detail', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function bookingDetail(Booking $booking): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Check if user has permission to view this booking
        if ($booking->getCustomer() !== $user && $booking->getService()->getProvider() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('You do not have permission to view this booking.');
        }

        return $this->render('booking/detail.html.twig', [
            'booking' => $booking,
        ]);
    }

    /**
     * List user's bookings
     */
    #[Route('/my-bookings', name: 'app_booking_my_bookings')]
    #[IsGranted('ROLE_USER')]
    public function myBookings(EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $bookings = $em->getRepository(Booking::class)->findBy(
            ['customer' => $user],
            ['bookingDate' => 'DESC']
        );

        return $this->render('booking/my_bookings.html.twig', [
            'bookings' => $bookings,
        ]);
    }

    /**
     * List provider's bookings
     */
    #[Route('/provider-bookings', name: 'app_booking_provider_bookings')]
    #[IsGranted('ROLE_PROVIDER')]
    public function providerBookings(EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Get all bookings for services owned by this provider
        $bookings = $em->createQueryBuilder()
            ->select('b')
            ->addSelect('s')
            ->from(Booking::class, 'b')
            ->join('b.service', 's')
            ->where('s.provider = :provider')
            ->setParameter('provider', $user)
            ->orderBy('b.bookingDate', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('booking/provider_bookings.html.twig', [
            'bookings' => $bookings,
        ]);
    }

    /**
     * Update booking status
     */
    #[Route('/{id}/status', name: 'app_booking_status', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function updateStatus(Booking $booking, Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Only provider or admin can update status
        if ($booking->getService()->getProvider() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Only the service provider can update booking status.');
        }

        // Validate CSRF token
        if (!$this->isCsrfTokenValid('booking_status_' . $booking->getId(), $request->request->get('_token'))) {
            return new JsonResponse(['error' => 'Invalid CSRF token'], 403);
        }

        $newStatus = $request->request->get('status');
        $allowedStatuses = ['pending', 'confirmed', 'accepted', 'in_progress', 'on-the-way', 'completed', 'cancelled'];

        if (!in_array($newStatus, $allowedStatuses)) {
            return new JsonResponse(['error' => 'Invalid status'], 400);
        }

        $oldStatus = $booking->getStatus();
        $booking->setStatus($newStatus);

        $em->flush();

        // Dispatch the appropriate event
        if ($newStatus === 'completed' && $oldStatus !== 'completed') {
            $this->dispatcher->dispatch(new BookingCompletedEvent($booking, $user));
        } else {
            $this->dispatcher->dispatch(new BookingStatusChangedEvent($booking, $oldStatus, $newStatus, $user));
        }

        $em->flush();

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'success' => true,
                'status' => $newStatus,
                'message' => 'Status updated to ' . ucfirst($newStatus)
            ]);
        }

        $this->addFlash('success', 'Booking status updated to ' . ucfirst($newStatus));
        return $this->redirectToRoute('app_booking_detail', ['id' => $booking->getId()]);
    }

    /**
     * Cancel booking
     */
    #[Route('/{id}/cancel', name: 'app_booking_cancel', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function cancelBooking(Booking $booking, Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Only customer or admin can cancel
        if ($booking->getCustomer() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Only the customer can cancel this booking.');
        }

        // Validate CSRF token
        if (!$this->isCsrfTokenValid('booking_cancel_' . $booking->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token.');
            return $this->redirectToRoute('app_booking_detail', ['id' => $booking->getId()]);
        }

        // Can only cancel pending or confirmed bookings
        if (!in_array($booking->getStatus(), ['pending', 'confirmed'])) {
            $this->addFlash('error', 'Cannot cancel booking with status: ' . $booking->getStatus());
            return $this->redirectToRoute('app_booking_detail', ['id' => $booking->getId()]);
        }

        $oldStatus = $booking->getStatus();
        $booking->setStatus('cancelled');
        $em->flush();

        // Dispatch event — listeners handle notification to provider + audit log
        $this->dispatcher->dispatch(new BookingStatusChangedEvent($booking, $oldStatus, 'cancelled', $user));
        $em->flush();

        $this->addFlash('success', 'Booking cancelled successfully.');
        return $this->redirectToRoute('app_booking_my_bookings');
    }

    /**
     * Update tracking location (for real-time tracking)
     */
    #[Route('/{id}/location', name: 'app_booking_location', methods: ['POST'])]
    #[IsGranted('ROLE_PROVIDER')]
    public function updateLocation(Booking $booking, Request $request, EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        // Only the service provider can update location
        if ($booking->getService()->getProvider() !== $user) {
            return new JsonResponse(['error' => 'Unauthorized'], 403);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['latitude']) || !isset($data['longitude'])) {
            return new JsonResponse(['error' => 'Latitude and longitude required'], 400);
        }

        $booking->setLatitude($data['latitude']);
        $booking->setLongitude($data['longitude']);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'tracking_id' => $booking->getTrackingId(),
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude']
        ]);
    }

    /**
     * Get tracking info (for customer to track provider)
     */
    #[Route('/{id}/track', name: 'app_booking_track')]
    #[IsGranted('ROLE_USER')]
    public function trackBooking(Booking $booking): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Only customer or admin can track
        if ($booking->getCustomer() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('You do not have permission to track this booking.');
        }

        return $this->render('booking/track.html.twig', [
            'booking' => $booking,
        ]);
    }

    /**
     * API endpoint for tracking data
     */
    #[Route('/{id}/track-data', name: 'app_booking_track_data', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getTrackingData(Booking $booking): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($booking->getCustomer() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Unauthorized'], 403);
        }

        return new JsonResponse([
            'tracking_id' => $booking->getTrackingId(),
            'status' => $booking->getStatus(),
            'latitude' => $booking->getLatitude(),
            'longitude' => $booking->getLongitude(),
            'service_title' => $booking->getService()->getTitle(),
            'provider_name' => $booking->getService()->getProvider()->getFullName(),
            'updated_at' => $booking->getBookingDate()->format('Y-m-d H:i:s')
        ]);
    }

    /**
     * Submit estimation for offline booking
     */
    #[Route('/{id}/estimate', name: 'app_booking_estimate', methods: ['POST'])]
    #[IsGranted('ROLE_PROVIDER')]
    public function submitEstimation(Booking $booking, Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Only provider can submit estimation
        if ($booking->getService()->getProvider() !== $user) {
            throw $this->createAccessDeniedException('Only the service provider can submit estimations.');
        }

        if (!$this->isCsrfTokenValid('booking_estimate_' . $booking->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token.');
            return $this->redirectToRoute('app_booking_detail', ['id' => $booking->getId()]);
        }

        $estimatedCost = $request->request->get('estimated_cost');
        $estimationStatus = $request->request->get('estimation_status', 'pending');

        $booking->setEstimatedCost($estimatedCost);
        $booking->setEstimationStatus($estimationStatus);
        $em->flush();

        // Dispatch event — listeners handle notification to customer, audit log, billing
        $this->dispatcher->dispatch(new EstimationSentEvent($booking, $estimatedCost));
        $em->flush();

        $this->addFlash('success', 'Estimation submitted successfully.');
        return $this->redirectToRoute('app_booking_detail', ['id' => $booking->getId()]);
    }

    /**
     * Accept or reject estimation
     */
    #[Route('/{id}/estimate-response', name: 'app_booking_estimate_response', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function respondToEstimation(Booking $booking, Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Only customer can respond
        if ($booking->getCustomer() !== $user) {
            throw $this->createAccessDeniedException('Only the customer can respond to estimations.');
        }

        if (!$this->isCsrfTokenValid('booking_estimate_response_' . $booking->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token.');
            return $this->redirectToRoute('app_booking_detail', ['id' => $booking->getId()]);
        }

        $response = $request->request->get('response'); // 'accepted' or 'rejected'

        if (!in_array($response, ['accepted', 'rejected'])) {
            $this->addFlash('error', 'Invalid response.');
            return $this->redirectToRoute('app_booking_detail', ['id' => $booking->getId()]);
        }

        $booking->setEstimationStatus($response);

        if ($response === 'accepted') {
            $booking->setStatus('confirmed');
        }

        $em->flush();

        // Dispatch event — listeners handle notification to provider + audit log
        $this->dispatcher->dispatch(new EstimationRespondedEvent($booking, $response));
        $em->flush();

        $this->addFlash('success', 'Estimation ' . $response . ' successfully.');
        return $this->redirectToRoute('app_booking_detail', ['id' => $booking->getId()]);
    }

    /**
     * Provider dispatches a booking (sets status to on-the-way)
     */
    #[Route('/{id}/dispatch', name: 'app_provider_dispatch_booking', methods: ['POST'])]
    #[IsGranted('ROLE_PROVIDER')]
    public function dispatchBooking(Booking $booking, Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($booking->getService()->getProvider() !== $user) {
            throw $this->createAccessDeniedException('Only the service provider can dispatch this booking.');
        }

        if (!$this->isCsrfTokenValid('dispatch' . $booking->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token.');
            return $this->redirectToRoute('app_provider_dashboard');
        }

        if (!in_array($booking->getStatus(), ['confirmed', 'Confirmed', 'accepted'])) {
            $this->addFlash('error', 'Only confirmed bookings can be dispatched.');
            return $this->redirectToRoute('app_provider_dashboard');
        }

        $oldStatus = $booking->getStatus();
        $booking->setStatus('on-the-way');
        $em->flush();

        // Dispatch event — listeners handle notification to customer + audit log
        $this->dispatcher->dispatch(new BookingStatusChangedEvent($booking, $oldStatus, 'on-the-way', $user));
        $em->flush();

        $this->addFlash('success', 'Booking dispatched! Provider is now on the way.');
        return $this->redirectToRoute('app_provider_dashboard');
    }

    /**
     * Provider marks a booking as complete
     */
    #[Route('/{id}/complete', name: 'app_provider_complete_booking', methods: ['POST'])]
    #[IsGranted('ROLE_PROVIDER')]
    public function completeBooking(Booking $booking, Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($booking->getService()->getProvider() !== $user) {
            throw $this->createAccessDeniedException('Only the service provider can complete this booking.');
        }

        if (!$this->isCsrfTokenValid('complete' . $booking->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token.');
            return $this->redirectToRoute('app_provider_dashboard');
        }

        if ($booking->getStatus() !== 'on-the-way') {
            $this->addFlash('error', 'Only on-the-way bookings can be marked complete.');
            return $this->redirectToRoute('app_provider_dashboard');
        }

        $booking->setStatus('completed');
        $em->flush();

        // Dispatch event — listeners handle notification, gamification, billing, audit
        $this->dispatcher->dispatch(new BookingCompletedEvent($booking, $user));
        $em->flush();

        $this->addFlash('success', 'Booking marked as completed. Reputation points awarded!');
        return $this->redirectToRoute('app_provider_dashboard');
    }
}
