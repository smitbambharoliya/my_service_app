<?php

namespace App\Controller;

use App\Entity\Billing;
use App\Entity\Booking;
use App\Entity\Service;
use App\Entity\User;
use App\Form\BookingType;
use App\Form\ServiceType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request; 
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ServiceController extends AbstractController
{
    #[Route('/services', name: 'app_service_list')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $query = $request->query->get('q');
        $category = $request->query->get('category');
        $priceRange = $request->query->get('price');

        $repo = $em->getRepository(Service::class);
        
        // If filters are active, use smartMatchSearch, else just get all active
        if ($query || $category || $priceRange) {
            $services = $repo->smartMatchSearch($query, $category, $priceRange);
        } else {
            $services = $repo->findBy(['isActive' => true], ['id' => 'DESC']);
        }
        
        return $this->render('service/index.html.twig', [
            'services' => $services,
            'active_query' => $query,
            'active_category' => $category,
            'active_price' => $priceRange,
        ]);
    }

    #[Route('/service/{id}', name: 'app_service_show', requirements: ['id' => '\d+'])]
    public function show(Service $service): Response
    {
        return $this->render('service/show.html.twig', [
            'service' => $service,
        ]);
    }

    #[Route('/service/new', name: 'app_service_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_PROVIDER');

        $service = new Service();
        $form = $this->createForm(ServiceType::class, $service);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $service->setProvider($this->getUser());
            $entityManager->persist($service);
            $entityManager->flush();

            $this->addFlash('success', 'Service added successfully!');
            return $this->redirectToRoute('app_provider_dashboard');
        }

        return $this->render('service/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // --- DASHBOARD LOGIC ---

    #[Route('/dashboard/provider/profile', name: 'app_provider_dashboard')]
    public function providerDashboard(EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_PROVIDER');
        $user = $this->getUser();

        $bookings = $entityManager->getRepository(Booking::class)->createQueryBuilder('b')
            ->join('b.service', 's')
            ->where('s.provider = :provider')
            ->setParameter('provider', $user)
            ->orderBy('b.bookingDate', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('dashboard/provider.html.twig', [
            'bookings' => $bookings,
        ]);
    }

    // --- BOOKING LOGIC ---

    #[Route('/book/{categoryName}', name: 'app_book_by_category')]
    public function bookByCategory(string $categoryName, EntityManagerInterface $em): Response
    {
        $services = $em->getRepository(Service::class)->findBy([
            'category' => $categoryName,
            'isActive' => true
        ]);

        // Redirect directly ONLY if exactly one service exists
        if (count($services) === 1) {
            return $this->redirectToRoute('app_service_show', ['id' => $services[0]->getId()]);
        }

        return $this->render('service/book_by_category.html.twig', [
            'services' => $services,
            'categoryName' => $categoryName
        ]);
    }

    #[Route('/book/service/{id}', name: 'app_service_book')]
    public function book(Service $service, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $booking = new Booking();
        $form = $this->createForm(BookingType::class, $booking);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $booking->setCustomer($this->getUser());
            $booking->setService($service);
            $booking->setStatus('pending');
            $booking->setBookingDate(new \DateTimeImmutable());

            $entityManager->persist($booking);
            $entityManager->flush();

            $this->addFlash('success', 'Your Booking Has Been Placed Successfully!');
            return $this->redirectToRoute('app_customer_dashboard');
        }

        return $this->render('booking/new.html.twig', [
            'form' => $form->createView(),
            'service' => $service
        ]);
    }

    // --- ESTIMATION (Provider sends estimate for Visit bookings) ---

    #[Route('/dashboard/provider/booking/{id}/estimate', name: 'app_provider_send_estimate', methods: ['POST'])]
    public function sendEstimation(Booking $booking, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_PROVIDER');

        // Ensure this booking belongs to this provider's service
        if ($booking->getService()->getProvider() !== $this->getUser()) {
            $this->addFlash('danger', 'Access denied.');
            return $this->redirectToRoute('app_provider_dashboard');
        }

        if ($this->isCsrfTokenValid('estimate' . $booking->getId(), $request->request->get('_token'))) {
            $estimatedCost = $request->request->get('estimatedCost');
            if ($estimatedCost && is_numeric($estimatedCost) && $estimatedCost > 0) {
                $booking->setEstimatedCost((string) $estimatedCost);
                $booking->setEstimationStatus('sent');
                
                // Generate Estimate Bill
                $billing = new Billing();
                $billing->setUser($booking->getCustomer());
                $billing->setAmount((string) $estimatedCost);
                $billing->setPaymentStatus('estimate');
                $billing->setTransactionId('EST-' . strtoupper(substr(uniqid(), -6)));
                $billing->setCreatedAt(new \DateTimeImmutable());
                $billing->setCategory($booking->getService()->getCategory());
                $billing->setServiceName($booking->getService()->getTitle());
                $billing->setDescription('Estimated cost provided by the professional for the visit.');
                $entityManager->persist($billing);

                $entityManager->flush();
                $this->addFlash('success', 'Estimation of ₹' . $estimatedCost . ' sent to customer. Estimate bill created.');
            } else {
                $this->addFlash('danger', 'Please enter a valid estimated cost.');
            }
        }

        return $this->redirectToRoute('app_provider_dashboard');
    }    #[Route('/dashboard/provider/booking/{id}/complete', name: 'app_provider_complete_booking', methods: ['POST'])]
    public function completeBooking(Booking $booking, Request $request, EntityManagerInterface $entityManager, \App\Service\GamificationService $gamification): Response
    {
        $this->denyAccessUnlessGranted('ROLE_PROVIDER');

        if ($booking->getService()->getProvider() !== $this->getUser()) {
            $this->addFlash('danger', 'Access denied.');
            return $this->redirectToRoute('app_provider_dashboard');
        }

        if ($this->isCsrfTokenValid('complete' . $booking->getId(), $request->request->get('_token'))) {
            if ($booking->getBookingType() === 'visit' && $booking->getEstimationStatus() !== 'accepted') {
                $this->addFlash('danger', 'Cannot complete: estimate must be accepted first.');
                return $this->redirectToRoute('app_provider_dashboard');
            }

            if ($booking->getStatus() !== 'completed') {
                $booking->setStatus('completed');

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
                $billing->setCategory($booking->getService()->getCategory());
                $billing->setServiceName($booking->getService()->getTitle());
                $billing->setDescription('Final bill for completed service request.');
                
                $entityManager->persist($billing);
                $gamification->awardPoints($booking->getService()->getProvider(), 500, false);
                $entityManager->flush();

                $this->addFlash('success', 'Booking completed & final bill generated. You earned 500 Reputation Points! 🏆');
            }
        }

        return $this->redirectToRoute('app_provider_dashboard');
    }

    #[Route('/dashboard/provider/booking/{id}/dispatch', name: 'app_provider_dispatch_booking', methods: ['POST'])]
    public function dispatchBooking(Booking $booking, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_PROVIDER');

        if ($booking->getService()->getProvider() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('dispatch' . $booking->getId(), $request->request->get('_token'))) {
            $booking->setStatus('on-the-way');
            $entityManager->flush();
            $this->addFlash('success', 'Protocol Status: DISPATCHED. Node is currently transit to sector.');
        }

        return $this->redirectToRoute('app_provider_dashboard');
    }

    #[Route('/premium/upgrade', name: 'app_premium_upgrade')]
    public function upgradeToPremium(EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $billing = new Billing();
        $billing->setUser($user);
        $billing->setAmount(999.00);
        $billing->setPaymentStatus('success');
        $billing->setTransactionId('TXN' . strtoupper(uniqid()));
        $billing->setCreatedAt(new \DateTimeImmutable());
        
        $entityManager->persist($billing);

        /** @var User $user */
        foreach ($user->getServices() as $service) {
            $service->setIsPremium(true);
        }

        $entityManager->flush();
        $this->addFlash('success', 'Upgraded to Premium!');

        return $this->redirectToRoute('app_provider_dashboard');
    }


    #[Route('/dashboard/provider/my-services', name:'app_provider_my_services')]
    public function myServices():Response
    {
        $this->denyAccessUnlessGranted('ROLE_PROVIDER');

        /** @var User $user */
        $user = $this->getUser();

        $services = $user->getServices();


        return $this->render('dashboard/my_services.html.twig',[
            'services' => $services,
        ]);
    }
}