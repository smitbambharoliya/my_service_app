<?php

namespace App\Controller;

use App\Entity\Billing;
use App\Entity\Booking;
use App\Entity\Service;
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
    public function list(EntityManagerInterface $entityManager): Response
    {
        // Premium services list ma upar dekhay
        $services = $entityManager->getRepository(Service::class)->findBy([], ['isPremium' => 'DESC']);

        return $this->render('service/list.html.twig', [
            'services' => $services,
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

        // Provider ni services par jetla bookings aaya hoy te shoďo
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

    #[Route('/book-by-category/{categoryName}', name: 'app_book_by_category')]
    public function bookByCategory(string $categoryName, EntityManagerInterface $entityManager): Response
    {
        $services = $entityManager->getRepository(Service::class)->findBy(['category' => $categoryName]);

        if (!$services) {
            $this->addFlash('error', "Hali a category ma koi service available nathi.");
            return $this->redirectToRoute('app_service_list');
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
        $booking->setCustomer($this->getUser());
        $booking->setService($service);
        $booking->setStatus('pending');
        $booking->setBookingDate(new \DateTimeImmutable());

        $form = $this->createForm(BookingType::class, $booking);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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

    #[Route('/premium/upgrade', name: 'app_premium_upgrade')]
    public function upgradeToPremium(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $billing = new Billing();
        $billing->setUser($user);
        $billing->setAmount(999.00);
        $billing->setPaymentStatus('success');
        $billing->setTransactionId('TXN' . strtoupper(uniqid()));
        $billing->setCreatedAt(new \DateTimeImmutable());
        
        $entityManager->persist($billing);

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

        $user = $this->getUser();

        $services = $user->getServices();


        return $this->render('dashboard/my_services.html.twig',[
            'services' => $services,
        ]);
    }
}