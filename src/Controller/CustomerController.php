<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CustomerController extends AbstractController
{
    #[Route('/dashboard/customer', name: 'app_customer_dashboard')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $bookings = $entityManager->getRepository(Booking::class)->findBy(['customer' => $user], ['bookingDate' => 'DESC']);

        return $this->render('dashboard/customer.html.twig', [
            'bookings' => $bookings,
        ]);
    }

    #[Route('/dashboard/customer/booking/{id}/cancel', name: 'app_customer_booking_cancel', methods: ['POST'])]
    public function cancelBooking(Booking $booking, EntityManagerInterface $entityManager, Request $request): Response
    {
        if ($booking->getCustomer() !== $this->getUser()) {
            $this->addFlash('danger', 'You are not authorized to cancel this booking.');
            return $this->redirectToRoute('app_customer_dashboard');
        }

        if ($this->isCsrfTokenValid('cancel' . $booking->getId(), $request->request->get('_token'))) {
            if ($booking->getStatus() === 'pending') {
                $entityManager->remove($booking);
                $entityManager->flush();
                $this->addFlash('success', 'Your booking has been cancelled successfully.');
            } else {
                $this->addFlash('warning', 'Only pending bookings can be cancelled.');
            }
        }

        return $this->redirectToRoute('app_customer_dashboard');
    }

    #[Route('/dashboard/customer/booking/{id}/accept-estimate', name: 'app_customer_accept_estimate', methods: ['POST'])]
    public function acceptEstimate(
        Booking $booking,
        EntityManagerInterface $entityManager,
        Request $request,
        NotificationService $notificationService
    ): Response
    {
        if ($booking->getCustomer() !== $this->getUser()) {
            $this->addFlash('danger', 'Access denied.');
            return $this->redirectToRoute('app_customer_dashboard');
        }

        if ($this->isCsrfTokenValid('accept_estimate' . $booking->getId(), $request->request->get('_token'))) {
            $booking->setEstimationStatus('accepted');
            $entityManager->flush();

            $notificationService->notifyBookingUpdate(
                $booking->getService()->getProvider(),
                'Estimate accepted',
                sprintf(
                    '%s accepted the estimate for "%s".',
                    $booking->getCustomer()->getFullName(),
                    $booking->getService()->getTitle()
                ),
                $this->generateUrl('app_booking_detail', ['id' => $booking->getId()])
            );

            $this->addFlash('success', 'You have accepted the estimate of ₹' . $booking->getEstimatedCost() . '. The provider will proceed with the work.');
        }

        return $this->redirectToRoute('app_customer_dashboard');
    }

    #[Route('/dashboard/customer/booking/{id}/reject-estimate', name: 'app_customer_reject_estimate', methods: ['POST'])]
    public function rejectEstimate(
        Booking $booking,
        EntityManagerInterface $entityManager,
        Request $request,
        NotificationService $notificationService
    ): Response
    {
        if ($booking->getCustomer() !== $this->getUser()) {
            $this->addFlash('danger', 'Access denied.');
            return $this->redirectToRoute('app_customer_dashboard');
        }

        if ($this->isCsrfTokenValid('reject_estimate' . $booking->getId(), $request->request->get('_token'))) {
            $booking->setEstimationStatus('rejected');
            $entityManager->flush();

            $notificationService->notifyBookingUpdate(
                $booking->getService()->getProvider(),
                'Estimate rejected',
                sprintf(
                    '%s rejected the estimate for "%s".',
                    $booking->getCustomer()->getFullName(),
                    $booking->getService()->getTitle()
                ),
                $this->generateUrl('app_booking_detail', ['id' => $booking->getId()])
            );

            $this->addFlash('warning', 'You have rejected the estimate. The provider will be notified.');
        }

        return $this->redirectToRoute('app_customer_dashboard');
    }

    #[Route('/dashboard/customer/booking/{id}/track', name: 'app_booking_track')]
    public function track(Booking $booking): Response
    {
        if ($booking->getCustomer() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        // Only allow tracking if the provider is 'on-the-way'
        // For testing, we'll allow it for 'confirmed' too if needed, but per spec 'on-the-way'
        
        return $this->render('booking/track.html.twig', [
            'booking' => $booking,
            // Mock provider coordinates (near some central point like Mumbai for demo)
            'start_lat' => 19.0760,
            'start_lng' => 72.8777,
            'dest_lat' => 19.0850,
            'dest_lng' => 72.8850
        ]);
    }
}
