<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class AdminAuditLogger
{
    private LoggerInterface $logger;
    private Security $security;

    public function __construct(LoggerInterface $logger, Security $security)
    {
        $this->logger = $logger;
        $this->security = $security;
    }

    /**
     * Log an admin action
     */
    public function log(string $action, string $entityType, ?int $entityId = null, array $details = []): void
    {
        $admin = $this->security->getUser();
        $adminEmail = $admin ? $admin->getUserIdentifier() : 'system';
        $adminId = $admin ? $admin->getId() : null;

        $logData = [
            'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            'admin_id' => $adminId,
            'admin_email' => $adminEmail,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'ip_address' => $this->getClientIp(),
            'details' => $details,
        ];

        $this->logger->info('[ADMIN_AUDIT] ' . json_encode($logData));
    }

    /**
     * Log user-related admin actions
     */
    public function logUserAction(string $action, int $userId, string $userEmail, array $details = []): void
    {
        $this->log($action, 'User', $userId, array_merge($details, ['target_user_email' => $userEmail]));
    }

    /**
     * Log category-related admin actions
     */
    public function logCategoryAction(string $action, int $categoryId, string $categoryName, array $details = []): void
    {
        $this->log($action, 'Category', $categoryId, array_merge($details, ['category_name' => $categoryName]));
    }

    /**
     * Log service-related admin actions
     */
    public function logServiceAction(string $action, int $serviceId, string $serviceTitle, array $details = []): void
    {
        $this->log($action, 'Service', $serviceId, array_merge($details, ['service_title' => $serviceTitle]));
    }

    /**
     * Log booking-related admin actions
     */
    public function logBookingAction(string $action, int $bookingId, array $details = []): void
    {
        $this->log($action, 'Booking', $bookingId, $details);
    }

    /**
     * Log authentication-related admin actions
     */
    public function logAuthAction(string $action, array $details = []): void
    {
        $this->log($action, 'Auth', null, $details);
    }

    private function getClientIp(): string
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}
