<?php

namespace App\Controller;

use App\Constants\AppConstants;
use App\Entity\Billing;
use App\Entity\Booking;
use App\Entity\Review;
use App\Entity\Service;
use App\Entity\User;
use App\Event\BookingCompletedEvent;
use App\Event\BookingCreatedEvent;
use App\Event\BookingStatusChangedEvent;
use App\Event\EstimationSentEvent;
use App\Form\BookingType;
use App\Form\ServiceType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class ServiceController extends AbstractController
{
    #[Route('/services', name: 'app_service_list')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $query = $request->query->get('q');
        $category = $request->query->get('category');
        $priceRange = $request->query->get('price');
        $tier = $request->query->get('tier');
        $isPremium = $request->query->get('premium');

        $categories = $em->getRepository(\App\Entity\Category::class)->findAll();

        $repo = $em->getRepository(Service::class);
        
        // If filters are active, use smartMatchSearch, else use optimized query
        if ($query || $category || $priceRange || $tier || $isPremium !== null) {
            $services = $repo->smartMatchSearch($query, $category, $priceRange, $tier, $isPremium);
        } else {
            $services = $repo->findActiveWithProvider();
        }
        
        return $this->render('service/index.html.twig', [
            'services' => $services,
            'active_query' => $query,
            'active_category' => $category,
            'active_price' => $priceRange,
            'active_tier' => $tier,
            'active_premium' => $isPremium,
            'all_categories' => $categories,
        ]);
    }

    #[Route('/service/{id}', name: 'app_service_show', requirements: ['id' => '\d+'])]
    public function show(Service $service, EntityManagerInterface $em): Response
    {
        $provider = $service->getProvider();
        $providerReviews = [];
        $providerAverageRating = null;
        $providerCompletedJobs = 0;
        $providerActiveServices = [];
        $providerOtherServices = [];

        if ($provider instanceof User) {
            // Get provider stats using optimized queries
            $userRepo = $em->getRepository(User::class);
            $stats = $userRepo->getProviderStats($provider);
            
            $providerCompletedJobs = $stats['completed_jobs'];
            $providerAverageRating = $stats['average_rating'];
            
            // Get other services
            $providerOtherServices = $userRepo->getProviderActiveServices($provider, $service);
        }

        return $this->render('service/show.html.twig', [
            'service' => $service,
            'provider' => $provider,
            'provider_reviews' => array_slice($providerReviews, 0, 6),
            'provider_average_rating' => $providerAverageRating,
            'provider_review_count' => count($providerReviews),
            'provider_completed_jobs' => $providerCompletedJobs,
            'provider_active_services_count' => count($providerActiveServices),
            'provider_other_services' => $providerOtherServices,
        ]);
    }

    #[Route('/service/new', name: 'app_service_new')]
    public function new(Request $request, EntityManagerInterface $entityManager, \Symfony\Component\String\Slugger\SluggerInterface $slugger): Response
    {
        $this->denyAccessUnlessGranted('ROLE_PROVIDER');

        /** @var User $user */
        $user = $this->getUser();

        // Hard checkpoint for new providers: require basic profile before creating services
        if (!$user->getAddress() || !$user->getCity() || !$user->getMobile()) {
            $this->addFlash('warning', 'Please complete your profile (address, city and mobile) before publishing a service.');
            return $this->redirectToRoute('app_provider_onboarding');
        }

        $service = new Service();
        $form = $this->createForm(ServiceType::class, $service);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $imageFile */
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir').'/public/uploads/services',
                        $newFilename
                    );
                    $service->setImage($newFilename);
                } catch (\Symfony\Component\HttpFoundation\File\Exception\FileException $e) {
                    $this->addFlash('error', 'Image upload failed. Please try again.');
                }
            }

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
        /** @var User $user */
        $user = $this->getUser();

        $bookings = $entityManager->getRepository(Booking::class)->createQueryBuilder('b')
            ->join('b.service', 's')
            ->where('s.provider = :provider')
            ->setParameter('provider', $user)
            ->orderBy('b.bookingDate', 'DESC')
            ->getQuery()
            ->getResult();

        // Calculate Monthly Yield (Last 6 Months)
        $chartLabels = [];
        $chartData = [];
        
        for ($i = 5; $i >= 0; $i--) {
            $monthStart = (new \DateTimeImmutable("first day of -$i month"))->setTime(0,0,0);
            $monthEnd = (new \DateTimeImmutable("last day of -$i month"))->setTime(23,59,59);
            
            $chartLabels[] = $monthStart->format('M');
            
            $yield = 0;
            foreach ($bookings as $b) {
                if ($b->getStatus() === 'completed' && $b->getBookingDate() >= $monthStart && $b->getBookingDate() <= $monthEnd) {
                    $yield += (float) ($b->getEstimatedCost() ?: $b->getService()->getPrice());
                }
            }
            $chartData[] = $yield;
        }

        return $this->render('dashboard/provider.html.twig', [
            'bookings' => $bookings,
            'chartLabels' => json_encode($chartLabels),
            'chartData' => json_encode($chartData),
        ]);
    }

    #[Route('/dashboard/provider/onboarding', name: 'app_provider_onboarding')]
    public function providerOnboarding(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_PROVIDER');

        return $this->render('dashboard/provider_onboarding.html.twig');
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
    public function book(
        Service $service,
        Request $request,
        EntityManagerInterface $entityManager,
        EventDispatcherInterface $dispatcher
    ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $booking = new Booking();
        $form = $this->createForm(BookingType::class, $booking);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $user */
            $user = $this->getUser();
            $booking->setCustomer($user);
            $booking->setService($service);
            $booking->setStatus('pending');
            $booking->setBookingDate(new \DateTimeImmutable());

            $entityManager->persist($booking);
            $entityManager->flush();

            // Dispatch event — listeners handle notifications, audit, gamification
            $dispatcher->dispatch(new BookingCreatedEvent($booking, $user));
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
    public function sendEstimation(
        Booking $booking,
        Request $request,
        EntityManagerInterface $entityManager,
        EventDispatcherInterface $dispatcher
    ): Response
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
                $entityManager->flush();

                // Dispatch event — listeners handle billing generation, notification, audit
                $dispatcher->dispatch(new EstimationSentEvent($booking, (string) $estimatedCost));
                $entityManager->flush();

                $this->addFlash('success', 'Estimation of ₹' . $estimatedCost . ' sent to customer. Estimate bill created.');
            } else {
                $this->addFlash('danger', 'Please enter a valid estimated cost.');
            }
        }

        return $this->redirectToRoute('app_provider_dashboard');
    }    #[Route('/dashboard/provider/booking/{id}/complete', name: 'app_provider_complete_booking', methods: ['POST'])]
    public function completeBooking(
        Booking $booking,
        Request $request,
        EntityManagerInterface $entityManager,
        EventDispatcherInterface $dispatcher
    ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_PROVIDER');

        /** @var User $user */
        $user = $this->getUser();

        if ($booking->getService()->getProvider() !== $user) {
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
                $entityManager->flush();

                // Dispatch event — listeners handle billing, gamification, notification, audit
                $dispatcher->dispatch(new BookingCompletedEvent($booking, $user));
                $entityManager->flush();

                $this->addFlash('success', 'Booking completed & final bill generated. You earned Reputation Points! 🏆');
            }
        }

        return $this->redirectToRoute('app_provider_dashboard');
    }

    #[Route('/dashboard/provider/booking/{id}/dispatch', name: 'app_provider_dispatch_booking', methods: ['POST'])]
    public function dispatchBooking(
        Booking $booking,
        Request $request,
        EntityManagerInterface $entityManager,
        EventDispatcherInterface $dispatcher
    ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_PROVIDER');

        /** @var User $user */
        $user = $this->getUser();

        if ($booking->getService()->getProvider() !== $user) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('dispatch' . $booking->getId(), $request->request->get('_token'))) {
            $oldStatus = $booking->getStatus();
            $booking->setStatus('on-the-way');
            
            // Set initial tracking coordinates (from provider's profile or default)
            $provider = $booking->getService()->getProvider();
            $booking->setLatitude($provider->getLatitude() ?? '21.1702'); // Default Surat lat
            $booking->setLongitude($provider->getLongitude() ?? '72.8311'); // Default Surat long
            
            $entityManager->flush();

            // Dispatch event — listeners handle notification to customer + audit
            $dispatcher->dispatch(new BookingStatusChangedEvent($booking, $oldStatus, 'on-the-way', $user));
            $entityManager->flush();

            $this->addFlash('success', 'Protocol Status: DISPATCHED. Node is currently transit to sector.');
        }

        return $this->redirectToRoute('app_provider_dashboard');
    }

    #[Route('/booking/{id}/track', name: 'app_booking_track')]
    public function trackLive(Booking $booking): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        // Ensure only customer or provider can track
        if ($booking->getCustomer() !== $this->getUser() && $booking->getService()->getProvider() !== $this->getUser()) {
             throw $this->createAccessDeniedException();
        }

        return $this->render('booking/track.html.twig', [
            'booking' => $booking,
        ]);
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
