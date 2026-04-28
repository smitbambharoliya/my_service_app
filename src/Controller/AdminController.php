<?php

namespace App\Controller;

use App\Constants\AppConstants;
use App\Entity\Booking;
use App\Entity\Category;
use App\Entity\Service;
use App\Entity\User;
use App\Service\AdminAuditLogger;
use App\Service\AdminService;
use App\Service\CategoryService;
use App\Service\DataExportService;
use App\Service\RevenueCalculationService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
final class AdminController extends AbstractController
{
    public function __construct(
        private AdminAuditLogger $auditLogger,
        private RevenueCalculationService $revenueCalculationService,
        private AdminService $adminService,
        private CategoryService $categoryService,
        private DataExportService $exportService
    ) {}

    #[Route('/dashboard', name: 'app_admin_dashboard')]
    public function dashboard(EntityManagerInterface $em): Response
    {
        $usersCount = $em->getRepository(User::class)->count([]);
        $servicesCount = $em->getRepository(Service::class)->count([]);
        $bookingsCount = $em->getRepository(Booking::class)->count([]);
        $categoriesCount = $em->getRepository(Category::class)->count(['isActive' => true]);

        $revenue = $this->revenueCalculationService->calculateTotalRevenue();

        $recentUsers = $em->getRepository(User::class)->findBy([], ['createdAt' => 'DESC'], AppConstants::RECENT_ITEMS_LIMIT);
        $recentBookings = $em->getRepository(Booking::class)->findBy([], ['bookingDate' => 'DESC'], AppConstants::RECENT_ITEMS_LIMIT);

        $topProviders = $em->createQueryBuilder()
            ->select('u')
            ->addSelect('COUNT(s.id) AS HIDDEN service_count')
            ->from(User::class, 'u')
            ->join('u.services', 's')
            ->groupBy('u.id')
            ->orderBy('service_count', 'DESC')
            ->setMaxResults(AppConstants::TOP_PROVIDERS_LIMIT)
            ->getQuery()
            ->getResult();

        $tierStats = $em->createQueryBuilder()
            ->select('u.tier', 'COUNT(u.id) as count')
            ->from(User::class, 'u')
            ->groupBy('u.tier')
            ->getQuery()
            ->getResult();

        $categories = $em->getRepository(Category::class)->findBy(['isActive' => true], ['sortOrder' => 'ASC'], 6);
        $categoryStats = [];
        foreach ($categories as $category) {
            $categoryStats[] = [
                'name' => $category->getName(),
                'color' => $category->getColor(),
                'serviceCount' => $category->getServiceCount()
            ];
        }
        
        $revenueTrend = $this->revenueCalculationService->getDailyRevenueTrend(7);
        $chartLabels = array_column($revenueTrend, 'label');
        $chartData = array_column($revenueTrend, 'revenue');

        $this->auditLogger->logAuthAction('DASHBOARD_VIEW');

        return $this->render('admin/dashboard.html.twig', [
            'users_count' => $usersCount,
            'services_count' => $servicesCount,
            'bookings_count' => $bookingsCount,
            'categories_count' => $categoriesCount,
            'revenue' => $revenue,
            'top_providers' => $topProviders,
            'recent_users' => $recentUsers,
            'recent_bookings' => $recentBookings,
            'tier_stats' => $tierStats,
            'category_stats' => $categoryStats,
            'chart_labels' => $chartLabels,
            'chart_data' => $chartData,
        ]);
    }

    #[Route('/users', name: 'app_admin_users')]
    public function manageUsers(EntityManagerInterface $em, PaginatorInterface $paginator, Request $request): Response
    {
        $search = trim((string) $request->query->get('search', ''));
        $roleFilter = $request->query->get('role');
        $statusFilter = $request->query->get('status');
        $sort = $request->query->get('sort', 'newest');

        $queryBuilder = $em->getRepository(User::class)->createQueryBuilder('u');

        if ($search !== '') {
            $queryBuilder
                ->andWhere('u.email LIKE :search OR u.fullName LIKE :search OR u.mobile LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($roleFilter) {
            $queryBuilder
                ->andWhere('u.roles LIKE :role')
                ->setParameter('role', '%' . $roleFilter . '%');
        }

        if ($statusFilter !== null && $statusFilter !== '') {
            $queryBuilder
                ->andWhere('u.isActive = :status')
                ->setParameter('status', $statusFilter === 'active');
        }

        $queryBuilder->orderBy('u.createdAt', $sort === 'oldest' ? 'ASC' : 'DESC');

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('admin/users.html.twig', [
            'users' => $pagination,
            'search' => $search,
            'role_filter' => $roleFilter,
            'status_filter' => $statusFilter,
            'sort_filter' => $sort,
        ]);
    }

    #[Route('/users/export', name: 'app_admin_users_export')]
    public function exportUsers(EntityManagerInterface $em): Response
    {
        $users = $em->getRepository(User::class)->findAll();
        
        return $this->exportService->exportToCsv(
            $users,
            'servicehub_users_' . date('Y-m-d'),
            ['ID', 'Full Name', 'Email', 'Mobile', 'Roles', 'Verified', 'Active', 'Created At'],
            function (User $user) {
                return [
                    $user->getId(),
                    $user->getFullName(),
                    $user->getEmail(),
                    $user->getMobile(),
                    implode(', ', $user->getRoles()),
                    $user->isVerified() ? 'Yes' : 'No',
                    $user->isActive() ? 'Yes' : 'No',
                    $user->getCreatedAt()->format('Y-m-d H:i:s')
                ];
            }
        );
    }

    #[Route('/users/{id}/promote', name: 'app_admin_user_promote', methods: ['POST'])]
    public function promoteUser(User $user, Request $request): Response
    {
        if ($this->isCsrfTokenValid('promote' . $user->getId(), $request->request->get('_token'))) {
            if ($this->adminService->promoteToAdmin($user)) {
                $this->addFlash('success', 'User promoted to Admin successfully.');
            } else {
                $this->addFlash('info', 'User is already an Admin.');
            }
        }

        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/users/{id}/delete', name: 'app_admin_user_delete', methods: ['POST'])]
    public function deleteUser(User $user, Request $request): Response
    {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            if ($user === $this->getUser()) {
                $this->addFlash('danger', 'You cannot delete your own admin account.');
                return $this->redirectToRoute('app_admin_users');
            }

            $this->adminService->deleteUser($user);
            $this->addFlash('success', 'User completely removed from the system.');
        }

        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/services', name: 'app_admin_services')]
    public function manageServices(EntityManagerInterface $em, PaginatorInterface $paginator, Request $request): Response
    {
        $search = trim((string) $request->query->get('search', ''));
        $statusFilter = $request->query->get('status');
        $premiumFilter = $request->query->get('premium');
        $sort = $request->query->get('sort', 'newest');

        $queryBuilder = $em->getRepository(Service::class)->createQueryBuilder('s')
            ->leftJoin('s.provider', 'p')
            ->leftJoin('s.category', 'c')
            ->addSelect('p', 'c');

        if ($search !== '') {
            $queryBuilder
                ->andWhere('s.title LIKE :search OR s.description LIKE :search OR p.fullName LIKE :search OR p.email LIKE :search OR c.name LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($statusFilter !== null && $statusFilter !== '') {
            $queryBuilder
                ->andWhere('s.isActive = :status')
                ->setParameter('status', $statusFilter === 'active');
        }

        if ($premiumFilter !== null && $premiumFilter !== '') {
            $queryBuilder
                ->andWhere('s.isPremium = :premium')
                ->setParameter('premium', $premiumFilter === 'premium');
        }

        $queryBuilder->orderBy('s.id', $sort === 'oldest' ? 'ASC' : 'DESC');

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('admin/services.html.twig', [
            'services' => $pagination,
            'search' => $search,
            'status_filter' => $statusFilter,
            'premium_filter' => $premiumFilter,
            'sort_filter' => $sort,
        ]);
    }

    #[Route('/services/{id}/toggle-premium', name: 'app_admin_service_toggle_premium', methods: ['POST'])]
    public function togglePremiumService(Service $service, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('toggle_premium' . $service->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Invalid CSRF token. Please try again.');
            return $this->redirectToRoute('app_admin_services');
        }

        $status = $this->adminService->toggleServicePremium($service);
        $this->addFlash('success', "Service '{$service->getTitle()}' is now {$status}.");

        return $this->redirectToRoute('app_admin_services');
    }

    #[Route('/services/{id}/delete', name: 'app_admin_service_delete', methods: ['POST'])]
    public function deleteService(Service $service, Request $request): Response
    {
        if ($this->isCsrfTokenValid('delete' . $service->getId(), $request->request->get('_token'))) {
            $this->adminService->deleteService($service);
            $this->addFlash('success', 'Service completely removed.');
        }

        return $this->redirectToRoute('app_admin_services');
    }

    #[Route('/bookings', name: 'app_admin_bookings')]
    public function manageBookings(EntityManagerInterface $em, PaginatorInterface $paginator, Request $request): Response
    {
        $search = trim((string) $request->query->get('search', ''));
        $statusFilter = $request->query->get('status');
        $sort = $request->query->get('sort', 'newest');

        $queryBuilder = $em->getRepository(Booking::class)->createQueryBuilder('b')
            ->join('b.customer', 'customer')
            ->join('b.service', 'service')
            ->leftJoin('service.provider', 'provider')
            ->addSelect('customer', 'service', 'provider');

        if ($search !== '') {
            $queryBuilder
                ->andWhere('b.trackingId LIKE :search OR customer.email LIKE :search OR customer.fullName LIKE :search OR service.title LIKE :search OR provider.email LIKE :search OR provider.fullName LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($statusFilter) {
            $queryBuilder
                ->andWhere('b.status = :status')
                ->setParameter('status', $statusFilter);
        }

        $queryBuilder->orderBy('b.bookingDate', $sort === 'oldest' ? 'ASC' : 'DESC');

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('admin/bookings.html.twig', [
            'bookings' => $pagination,
            'search' => $search,
            'status_filter' => $statusFilter,
            'sort_filter' => $sort,
        ]);
    }

    #[Route('/bookings/export', name: 'app_admin_bookings_export')]
    public function exportBookings(EntityManagerInterface $em): Response
    {
        $bookings = $em->getRepository(Booking::class)->findAll();
        
        return $this->exportService->exportToCsv(
            $bookings,
            'servicehub_bookings_' . date('Y-m-d'),
            ['ID', 'Tracking ID', 'Customer', 'Service', 'Provider', 'Status', 'Date', 'Amount'],
            function (Booking $booking) {
                return [
                    $booking->getId(),
                    $booking->getTrackingId(),
                    $booking->getCustomer()->getFullName(),
                    $booking->getService()->getTitle(),
                    $booking->getService()->getProvider()->getFullName(),
                    $booking->getStatus(),
                    $booking->getBookingDate()->format('Y-m-d H:i:s'),
                    $booking->getEstimatedCost() ?? 'N/A'
                ];
            }
        );
    }

    #[Route('/bookings/{id}/delete', name: 'app_admin_booking_delete', methods: ['POST'])]
    public function deleteBooking(Booking $booking, Request $request): Response
    {
        if ($this->isCsrfTokenValid('delete' . $booking->getId(), $request->request->get('_token'))) {
            $this->adminService->deleteBooking($booking);
            $this->addFlash('success', 'Booking removed successfully.');
        }

        return $this->redirectToRoute('app_admin_bookings');
    }

    #[Route('/bookings/{id}/status', name: 'app_admin_booking_status', methods: ['POST'])]
    public function updateBookingStatus(Booking $booking, Request $request): Response
    {
        if ($this->isCsrfTokenValid('status' . $booking->getId(), $request->request->get('_token'))) {
            $newStatus = $request->request->get('status');
            $this->adminService->updateBookingStatus($booking, $newStatus);
            $this->addFlash('success', 'Booking status updated to ' . ucfirst($newStatus) . '.');
        }

        return $this->redirectToRoute('app_admin_bookings');
    }

    #[Route('/users/{id}/toggle-status', name: 'app_admin_user_toggle_status', methods: ['POST'])]
    public function toggleUserStatus(User $user): Response
    {
        if ($user === $this->getUser()) {
            $this->addFlash('danger', 'You cannot suspend your own account.');
            return $this->redirectToRoute('app_admin_users');
        }

        $status = $this->adminService->toggleUserStatus($user);
        $this->addFlash('success', "User '{$user->getEmail()}' is now {$status}.");

        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/services/{id}/toggle-status', name: 'app_admin_service_toggle_status', methods: ['POST'])]
    public function toggleServiceStatus(Service $service): Response
    {
        $status = $this->adminService->toggleServiceStatus($service);
        $this->addFlash('success', "Service '{$service->getTitle()}' is now {$status}.");

        return $this->redirectToRoute('app_admin_services');
    }

    #[Route('/providers', name: 'app_admin_providers')]
    public function manageProviders(EntityManagerInterface $em, PaginatorInterface $paginator, Request $request): Response
    {
        $search = trim((string) $request->query->get('search', ''));
        $statusFilter = $request->query->get('status');
        $sort = $request->query->get('sort', 'newest');

        $queryBuilder = $em->getRepository(User::class)->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_PROVIDER%');

        if ($search !== '') {
            $queryBuilder
                ->andWhere('u.email LIKE :search OR u.fullName LIKE :search OR u.mobile LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($statusFilter !== null && $statusFilter !== '') {
            $queryBuilder
                ->andWhere('u.isActive = :status')
                ->setParameter('status', $statusFilter === 'active');
        }

        $queryBuilder->orderBy('u.createdAt', $sort === 'oldest' ? 'ASC' : 'DESC');

        $providers = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('admin/providers.html.twig', [
            'providers' => $providers,
            'search' => $search,
            'status_filter' => $statusFilter,
            'sort_filter' => $sort,
        ]);
    }

    #[Route('/users/new', name: 'app_admin_user_new', methods: ['GET', 'POST'])]
    public function newUser(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
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

    #[Route('/categories', name: 'app_admin_categories')]
    public function manageCategories(EntityManagerInterface $em, PaginatorInterface $paginator, Request $request): Response
    {
        $search = trim((string) $request->query->get('search', ''));
        $statusFilter = $request->query->get('status');
        $sort = $request->query->get('sort', 'sort_order');

        $queryBuilder = $em->getRepository(Category::class)->createQueryBuilder('c');

        if ($search !== '') {
            $queryBuilder
                ->andWhere('c.name LIKE :search OR c.slug LIKE :search OR c.description LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($statusFilter !== null && $statusFilter !== '') {
            $queryBuilder
                ->andWhere('c.isActive = :status')
                ->setParameter('status', $statusFilter === 'active');
        }

        match ($sort) {
            'newest' => $queryBuilder->orderBy('c.createdAt', 'DESC'),
            'oldest' => $queryBuilder->orderBy('c.createdAt', 'ASC'),
            'name' => $queryBuilder->orderBy('c.name', 'ASC'),
            default => $queryBuilder->orderBy('c.sortOrder', 'ASC')->addOrderBy('c.name', 'ASC'),
        };

        $categories = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            10
        );

        $this->auditLogger->logAuthAction('CATEGORIES_VIEW');

        return $this->render('admin/categories.html.twig', [
            'categories' => $categories,
            'search' => $search,
            'status_filter' => $statusFilter,
            'sort_filter' => $sort,
        ]);
    }

    #[Route('/categories/new', name: 'app_admin_category_new', methods: ['POST'])]
    public function newCategory(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('category_new', $request->request->get('_token'))) {
            $this->addFlash('danger', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_admin_categories');
        }

        $category = $this->categoryService->createCategory($request->request->all());
        $this->addFlash('success', "Category '{$category->getName()}' created successfully.");

        return $this->redirectToRoute('app_admin_categories');
    }

    #[Route('/categories/{id}/edit', name: 'app_admin_category_edit', methods: ['POST'])]
    public function editCategory(Category $category, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('category_edit' . $category->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_admin_categories');
        }

        $this->categoryService->updateCategory($category, $request->request->all());
        $this->addFlash('success', "Category '{$category->getName()}' updated successfully.");

        return $this->redirectToRoute('app_admin_categories');
    }

    #[Route('/categories/{id}/toggle', name: 'app_admin_category_toggle', methods: ['POST'])]
    public function toggleCategory(Category $category, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('category_toggle' . $category->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_admin_categories');
        }

        $status = $this->categoryService->toggleCategory($category);
        $this->addFlash('success', "Category '{$category->getName()}' is now {$status}.");

        return $this->redirectToRoute('app_admin_categories');
    }

    #[Route('/categories/{id}/delete', name: 'app_admin_category_delete', methods: ['POST'])]
    public function deleteCategory(Category $category, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('category_delete' . $category->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_admin_categories');
        }

        if ($this->categoryService->deleteCategory($category)) {
            $this->addFlash('success', "Category '{$category->getName()}' deleted successfully.");
        } else {
            $this->addFlash('warning', "Cannot delete category '{$category->getName()}' - it has associated services.");
        }

        return $this->redirectToRoute('app_admin_categories');
    }

    #[Route('/users/command-hub', name: 'app_admin_user_command_hub')]
    public function userCommandHub(EntityManagerInterface $em, PaginatorInterface $paginator, Request $request): Response
    {
        $search = trim((string) $request->query->get('search', ''));
        $roleFilter = $request->query->get('role');
        $statusFilter = $request->query->get('status');
        $sort = $request->query->get('sort', 'newest');

        $qb = $em->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u');

        if ($search !== '') {
            $qb->andWhere('u.email LIKE :search OR u.fullName LIKE :search OR u.mobile LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($roleFilter) {
            $qb->andWhere('u.roles LIKE :role')
               ->setParameter('role', '%' . $roleFilter . '%');
        }

        if ($statusFilter !== null && $statusFilter !== '') {
            $qb->andWhere('u.isActive = :status')
               ->setParameter('status', $statusFilter === 'active');
        }

        $qb->orderBy('u.createdAt', $sort === 'oldest' ? 'ASC' : 'DESC');
        $users = $paginator->paginate(
            $qb,
            $request->query->getInt('page', 1),
            12
        );

        $stats = [
            'total' => $em->getRepository(User::class)->count([]),
            'verified' => $em->getRepository(User::class)->count(['isVerified' => true]),
            'providers' => (int) $em->createQueryBuilder()->select('COUNT(u.id)')->from(User::class, 'u')->where('u.roles LIKE :role')->setParameter('role', '%ROLE_PROVIDER%')->getQuery()->getSingleScalarResult(),
            'admins' => (int) $em->createQueryBuilder()->select('COUNT(u.id)')->from(User::class, 'u')->where('u.roles LIKE :role')->setParameter('role', '%ROLE_ADMIN%')->getQuery()->getSingleScalarResult(),
            'suspended' => $em->getRepository(User::class)->count(['isActive' => false]),
        ];

        $this->auditLogger->logAuthAction('USER_COMMAND_HUB_VIEW');

        return $this->render('admin/user_command_hub.html.twig', [
            'users' => $users,
            'stats' => $stats,
            'search' => $search,
            'role_filter' => $roleFilter,
            'status_filter' => $statusFilter,
            'sort_filter' => $sort,
        ]);
    }

    #[Route('/users/{id}/verify', name: 'app_admin_user_verify', methods: ['POST'])]
    public function verifyUser(User $user, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('verify' . $user->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_admin_user_command_hub');
        }

        $this->adminService->verifyUser($user);
        $this->addFlash('success', "User '{$user->getEmail()}' has been verified.");

        return $this->redirectToRoute('app_admin_user_command_hub');
    }

    #[Route('/users/{id}/promote-provider', name: 'app_admin_user_promote_provider', methods: ['POST'])]
    public function promoteToProvider(User $user, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('promote_provider' . $user->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_admin_user_command_hub');
        }

        if ($this->adminService->promoteToProvider($user)) {
            $this->addFlash('success', "User '{$user->getEmail()}' promoted to Provider.");
        } else {
            $this->addFlash('info', "User '{$user->getEmail()}' is already a Provider.");
        }

        return $this->redirectToRoute('app_admin_user_command_hub');
    }

    #[Route('/users/{id}/demote-provider', name: 'app_admin_user_demote_provider', methods: ['POST'])]
    public function demoteFromProvider(User $user, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('demote_provider' . $user->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_admin_user_command_hub');
        }

        if ($user === $this->getUser()) {
            $this->addFlash('danger', 'You cannot modify your own roles.');
            return $this->redirectToRoute('app_admin_user_command_hub');
        }

        $this->adminService->demoteFromProvider($user);
        $this->addFlash('success', "Provider status removed from '{$user->getEmail()}'.");

        return $this->redirectToRoute('app_admin_user_command_hub');
    }

    #[Route('/users/{id}/reset-password', name: 'app_admin_user_reset_password', methods: ['POST'])]
    public function resetUserPassword(User $user, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('reset_password' . $user->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_admin_user_command_hub');
        }

        $newPassword = $request->request->get('new_password');
        if (strlen($newPassword) < 6) {
            $this->addFlash('danger', 'Password must be at least 6 characters.');
            return $this->redirectToRoute('app_admin_user_command_hub');
        }

        $this->adminService->resetUserPassword($user, $newPassword);
        $this->addFlash('success', "Password reset for '{$user->getEmail()}'.");

        return $this->redirectToRoute('app_admin_user_command_hub');
    }

    #[Route('/audit-logs', name: 'app_admin_audit_logs')]
    public function viewAuditLogs(PaginatorInterface $paginator, Request $request): Response
    {
        $logFile = $this->getParameter('kernel.logs_dir') . '/admin_actions.log';
        $logs = [];
        $search = trim((string) $request->query->get('search', ''));
        $actionFilter = $request->query->get('action');
        $sort = $request->query->get('sort', 'newest');

        if (file_exists($logFile)) {
            $lines = array_filter(array_map('trim', file($logFile)));
            $lines = array_reverse($lines);
            $lines = array_slice($lines, 0, 500);

            foreach ($lines as $line) {
                if (strpos($line, '[ADMIN_AUDIT]') !== false) {
                    $jsonStart = strpos($line, '{');
                    if ($jsonStart !== false) {
                        $jsonData = substr($line, $jsonStart);
                            $data = json_decode($jsonData, true);
                            if ($data) {
                                $logs[] = $data;
                        }
                    }
                }
            }
        }

        if ($search !== '') {
            $logs = array_values(array_filter($logs, function (array $log) use ($search): bool {
                $haystack = strtolower(json_encode($log));
                return str_contains($haystack, strtolower($search));
            }));
        }

        $actions = array_values(array_unique(array_filter(array_map(fn(array $log): ?string => $log['action'] ?? null, $logs))));
        sort($actions);

        if ($actionFilter) {
            $logs = array_values(array_filter($logs, fn(array $log): bool => ($log['action'] ?? '') === $actionFilter));
        }

        if ($sort === 'oldest') {
            $logs = array_reverse($logs);
        }

        $pagination = $paginator->paginate(
            $logs,
            $request->query->getInt('page', 1),
            12
        );

        return $this->render('admin/audit_logs.html.twig', [
            'logs' => $pagination,
            'actions' => $actions,
            'search' => $search,
            'action_filter' => $actionFilter,
            'sort_filter' => $sort,
        ]);
    }
}
