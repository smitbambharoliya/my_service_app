<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\Review;
use App\Event\ReviewSubmittedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class ReviewController extends AbstractController
{
    #[Route('/dashboard/customer/booking/{id}/review', name: 'app_customer_add_review', methods: ['POST'])]
    public function addReview(Booking $booking, Request $request, EntityManagerInterface $em, EventDispatcherInterface $dispatcher): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if ($booking->getCustomer() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($booking->getStatus() !== 'completed') {
            $this->addFlash('error', 'You can only review completed services.');
            return $this->redirectToRoute('app_customer_dashboard');
        }

        if ($booking->getReview()) {
            $this->addFlash('info', 'You have already submitted a review for this service.');
            return $this->redirectToRoute('app_customer_dashboard');
        }

        if ($this->isCsrfTokenValid('review' . $booking->getId(), $request->request->get('_token'))) {
            $rating = $request->request->get('rating');
            $comment = $request->request->get('comment');

            if ($rating && is_numeric($rating) && $rating >= 1 && $rating <= 5) {
                $review = new Review();
                $review->setBooking($booking);
                $review->setCustomer($this->getUser());
                $review->setProvider($booking->getService()->getProvider());
                $review->setRating((int) $rating);
                $review->setComment($comment);

                $em->persist($review);
                $em->flush();

                // Dispatch event — listeners handle notification to provider + gamification
                $dispatcher->dispatch(new ReviewSubmittedEvent($review));
                $em->flush();

                $this->addFlash('success', 'Thank you! Your appreciation has been registered in the archive.');
            } else {
                $this->addFlash('error', 'Please provide a valid rating between 1 and 5 stars.');
            }
        }

        return $this->redirectToRoute('app_customer_dashboard');
    }
}
