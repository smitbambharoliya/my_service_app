<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\Service;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
final class AdminController extends AbstractController
{
    #[Route('/dashboard', name: 'app_admin_dashboard')]
    public function dashboard(EntityManagerInterface $em): Response
    {
        $usersCount = $em->getRepository(User::class)->count([]);
        $servicesCount = $em->getRepository(Service::class)->count([]);
        $bookingsCount = $em->getRepository(Booking::class)->count([]);

        // Dummy revenue calculation for demo purposes (e.g. 500 per booking)
        $revenue = $bookingsCount * 500;

        // Get top providers (for demo, just take first 5 providers)
        $allUsers = $em->getRepository(User::class)->findAll();
        $providers = array_filter($allUsers, function($u) {
            return in_array('ROLE_PROVIDER', $u->getRoles());
        });
        usort($providers, function($a, $b) {
            return count($b->getServices()) <=> count($a->getServices());
        });
        $topProviders = array_slice($providers, 0, 5);

        return $this->render('admin/dashboard.html.twig', [
            'users_count' => $usersCount,
            'services_count' => $servicesCount,
            'bookings_count' => $bookingsCount,
            'revenue' => $revenue,
            'top_providers' => $topProviders,
        ]);
    }

    #[Route('/users', name: 'app_admin_users')]
    public function manageUsers(EntityManagerInterface $em): Response
    {
        $users = $em->getRepository(User::class)->findAll();
        
        return $this->render('admin/users.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/users/{id}/promote', name: 'app_admin_user_promote', methods: ['POST'])]
    public function promoteUser(User $user, EntityManagerInterface $em, Request $request): Response
    {
        if ($this->isCsrfTokenValid('promote'.$user->getId(), $request->request->get('_token'))) {
            $roles = $user->getRoles();
            if (!in_array('ROLE_ADMIN', $roles)) {
                $roles[] = 'ROLE_ADMIN';
                $user->setRoles($roles);
                $em->flush();
                $this->addFlash('success', 'User promoted to Admin successfully.');
            } else {
                $this->addFlash('info', 'User is already an Admin.');
            }
        }

        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/users/{id}/delete', name: 'app_admin_user_delete', methods: ['POST'])]
    public function deleteUser(User $user, EntityManagerInterface $em, Request $request): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            // Cannot delete yourself
            if ($user === $this->getUser()) {
                $this->addFlash('danger', 'You cannot delete your own admin account.');
                return $this->redirectToRoute('app_admin_users');
            }
            
            // Remove associated bookings and services to prevent constraint violations
            foreach ($user->getBookings() as $booking) {
                $em->remove($booking);
            }
            foreach ($user->getServices() as $service) {
                foreach ($service->getBookings() as $booking) {
                    $em->remove($booking);
                }
                $em->remove($service);
            }

            $em->remove($user);
            $em->flush();
            $this->addFlash('success', 'User completely removed from the system.');
        }

        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/services', name: 'app_admin_services')]
    public function manageServices(EntityManagerInterface $em): Response
    {
        $services = $em->getRepository(Service::class)->findAll();
        
        return $this->render('admin/services.html.twig', [
            'services' => $services,
        ]);
    }

    #[Route('/services/{id}/toggle-premium', name: 'app_admin_service_toggle_premium', methods: ['POST'])]
    public function togglePremiumService(Service $service, EntityManagerInterface $em): Response
    {
        $service->setIsPremium(!$service->isPremium());
        $em->flush();
        
        $status = $service->isPremium() ? 'Premium' : 'Standard';
        $this->addFlash('success', "Service '{$service->getTitle()}' is now {$status}.");

        return $this->redirectToRoute('app_admin_services');
    }

    #[Route('/services/{id}/delete', name: 'app_admin_service_delete', methods: ['POST'])]
    public function deleteService(Service $service, EntityManagerInterface $em, Request $request): Response
    {
        if ($this->isCsrfTokenValid('delete'.$service->getId(), $request->request->get('_token'))) {
            // Remove associated bookings first
            foreach ($service->getBookings() as $booking) {
                $em->remove($booking);
            }
            
            $em->remove($service);
            $em->flush();
            $this->addFlash('success', 'Service completely removed.');
        }

        return $this->redirectToRoute('app_admin_services');
    }

    #[Route('/bookings', name: 'app_admin_bookings')]
    public function manageBookings(EntityManagerInterface $em): Response
    {
        $bookings = $em->getRepository(Booking::class)->findAll();
        
        return $this->render('admin/bookings.html.twig', [
            'bookings' => $bookings,
        ]);
    }

    #[Route('/bookings/{id}/delete', name: 'app_admin_booking_delete', methods: ['POST'])]
    public function deleteBooking(Booking $booking, EntityManagerInterface $em, Request $request): Response
    {
        if ($this->isCsrfTokenValid('delete'.$booking->getId(), $request->request->get('_token'))) {
            $em->remove($booking);
            $em->flush();
            $this->addFlash('success', 'Booking removed successfully.');
        }

        return $this->redirectToRoute('app_admin_bookings');
    }

    #[Route('/bookings/{id}/status', name: 'app_admin_booking_status', methods: ['POST'])]
    public function updateBookingStatus(Booking $booking, EntityManagerInterface $em, Request $request): Response
    {
        if ($this->isCsrfTokenValid('status'.$booking->getId(), $request->request->get('_token'))) {
            $newStatus = $request->request->get('status');
            $allowed = ['pending', 'confirmed', 'completed'];
            if (in_array($newStatus, $allowed)) {
                $booking->setStatus($newStatus);
                $em->flush();
                $this->addFlash('success', 'Booking status updated to ' . ucfirst($newStatus) . '.');
            }
        }

        return $this->redirectToRoute('app_admin_bookings');
    }

    #[Route('/users/{id}/toggle-status', name: 'app_admin_user_toggle_status', methods: ['POST'])]
    public function toggleUserStatus(User $user, EntityManagerInterface $em): Response
    {
        if ($user === $this->getUser()) {
            $this->addFlash('danger', 'You cannot suspend your own account.');
            return $this->redirectToRoute('app_admin_users');
        }

        $user->setIsActive(!$user->isActive());
        $em->flush();

        $status = $user->isActive() ? 'Activated' : 'Suspended';
        $this->addFlash('success', "User '{$user->getEmail()}' is now {$status}.");

        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/services/{id}/toggle-status', name: 'app_admin_service_toggle_status', methods: ['POST'])]
    public function toggleServiceStatus(Service $service, EntityManagerInterface $em): Response
    {
        $service->setIsActive(!$service->isActive());
        $em->flush();

        $status = $service->isActive() ? 'Visible' : 'Hidden';
        $this->addFlash('success', "Service '{$service->getTitle()}' is now {$status}.");

        return $this->redirectToRoute('app_admin_services');
    }

    #[Route('/providers', name: 'app_admin_providers')]
    public function manageProviders(EntityManagerInterface $em): Response
    {
        // Find providers - in a real app would use a custom repository method
        $allUsers = $em->getRepository(User::class)->findAll();
        $providers = array_filter($allUsers, function($user) {
            return in_array('ROLE_PROVIDER', $user->getRoles());
        });

        return $this->render('admin/providers.html.twig', [
            'providers' => $providers,
        ]);
    }

    #[Route('/users/new', name: 'app_admin_user_new', methods: ['GET', 'POST'])]
    public function newUser(Request $request, EntityManagerInterface $em, \Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface $hasher): Response
    {
        if ($request->isMethod('POST')) {
            $user = new User();
            $user->setEmail($request->request->get('email'));
            $user->setFullName($request->request->get('fullName'));
            $user->setMobile($request->request->get('mobile'));
            $user->setRoles([$request->request->get('role')]);
            $user->setPassword($hasher->hashPassword($user, $request->request->get('password')));
            $user->setIsVerified(true);
            $user->setIsActive(true);

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'New user created successfully.');
            return $this->redirectToRoute('app_admin_users');
        }

        return $this->render('admin/user_new.html.twig');
    }
}
