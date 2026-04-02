<?php

namespace App\Controller;

use App\Entity\Booking;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CustomerController extends AbstractController
{
    #[Route('/dashboard/customer', name: 'app_customer_dashboard')]
    public function index(EntityManagerInterface $entityManager): Response
    {

         $user= $this->getUser();
         $bookings = $entityManager->getRepository(Booking::class)->findBy(['customer' => $user]);
        return $this->render('dashboard/customer.html.twig', [
            'controller_name' => 'CustomerController',
            'bookings' => $bookings,
        ]);
    }
}
