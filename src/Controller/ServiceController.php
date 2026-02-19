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
    #[Route('/service', name: 'app_service')]
    public function index(): Response
    {
        return $this->render('service/index.html.twig', [
            'controller_name' => 'ServiceController',
        ]);
    }

    #[Route('/service/new', name: 'app_service_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Fakt Service Provider j aa page access kari shake
        $this->denyAccessUnlessGranted('ROLE_PROVIDER');

        $service = new Service();
        $form = $this->createForm(ServiceType::class, $service);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
         
            $service->setProvider($this->getUser());

            $entityManager->persist($service);
            $entityManager->flush();

            return $this->redirectToRoute('app_service_list');
        }

        return $this->render('service/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/services', name: 'app_service_list')]
    public function list(EntityManagerInterface $entityManager): Response
    {
        // Premium services upar dekhay te mate logic:
        $services = $entityManager->getRepository(Service::class)->findBy([], ['isPremium' => 'DESC']);

        return $this->render('service/list.html.twig', [
            'services' => $services,
        ]);
    }


    #[Route('/dashbord/provider', name:'app_provider_dashboard')]
    public function  providerDashboard(EntityManagerInterface $entityManager): Response
    {

    $this->denyAccessUnlessGranted('ROLE_PROVIDER');
    $user = $this->getUser();


    $bookings = $entityManager->getRepository(Booking::class)->createQueryBuilder('b')
        ->join('b.service', 's')
        ->where('s.provider = :provider')
        ->setParameter('provider', $user)
        ->getQuery()
        ->getResult();


        return $this->render('dashboard/provider.html.twig',[
            'bookings' => $bookings,
        ]);

    }

    public function upgradeToPremium(EntityManagerInterface $entityManager): Response
    {

     $user = $this->getUser();

     $billing = new Billing();
     $billing ->setUser($user);
     $billing ->setAmount(999.00);
     $billing ->setPaymentStatus('success');
     $billing ->setTransactionId('TXN'. uniqid());
     $billing ->setCreatedAt(new \DateTimeImmutable());
     $entityManager->persist($billing);


     foreach ($user->getServices() as $service) {
          $service->setIsPremium(true);
     }

     $entityManager->flush();
     $this->addFlash('success', 'Congratulations! Your Account Has Been Upgraded To Premium. Your Services Are Now Featured For Better Visibility.');

     return $this->redirectToRoute('app_provider_dashboard');
    }


    #[Route('/book/service/{id}', name: 'app_service_book')]
    public function book(Service $service, Request $request, EntityManagerInterface $entityManager):Response
    {
  
    $this->denyAccessUnlessGranted('ROLE_CUSTOMER');


    $booking = new Booking();
    $booking ->setCustomer($this->getUser());
    $booking ->setService($service);
    $booking ->setStatus('pending');
    $booking ->setBookingDate(new \DateTimeImmutable());
    

    $form = $this->createForm(BookingType::class, $booking);
    $form->handleRequest($request);

    

    }


}