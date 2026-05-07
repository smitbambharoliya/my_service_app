<?php

namespace App\Service;

use App\Constants\AppConstants;
use App\Entity\Booking;
use App\Entity\Service;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AdminService
{
    public function __construct(
        private EntityManagerInterface $em,
        private AdminAuditLogger $auditLogger,
        private UserPasswordHasherInterface $hasher
    ) {
    }

    public function promoteToAdmin(User $user): bool
    {
        $roles = $user->getRoles();
        if (!in_array(AppConstants::ROLE_ADMIN, $roles, true)) {
            $roles[] = AppConstants::ROLE_ADMIN;
            $user->setRoles($roles);
            $this->em->flush();
            $this->auditLogger->logUserAction('USER_PROMOTE_ADMIN', $user->getId(), $user->getEmail());
            return true;
        }
        return false;
    }

    public function promoteToProvider(User $user): bool
    {
        $roles = $user->getRoles();
        if (!in_array(AppConstants::ROLE_PROVIDER, $roles, true)) {
            $roles[] = AppConstants::ROLE_PROVIDER;
            $user->setRoles($roles);
            $this->em->flush();
            $this->auditLogger->logUserAction('USER_PROMOTE_PROVIDER', $user->getId(), $user->getEmail());
            return true;
        }
        return false;
    }

    /**
     * Removes the ROLE_PROVIDER role from a user.
     */
    public function demoteFromProvider(User $user): void
    {
        $roles = array_diff($user->getRoles(), [AppConstants::ROLE_PROVIDER]);
        $user->setRoles(array_values($roles));
        $this->em->flush();
        $this->auditLogger->logUserAction('USER_DEMOTE_PROVIDER', $user->getId(), $user->getEmail());
    }

    /**
     * Deletes a user and all associated services/bookings inside a single transaction.
     * Uses bulk DQL deletes to avoid the N+1 query problem.
     */
    public function deleteUser(User $user): void
    {
        $userId = $user->getId();
        $userEmail = $user->getEmail();

        $this->em->wrapInTransaction(function () use ($user, $userId, $userEmail): void {
            // Delete all bookings made BY the customer
            $this->em->createQuery(
                'DELETE FROM App\Entity\Booking b WHERE b.customer = :user'
            )->setParameter('user', $user)->execute();

            // Delete all bookings for services OWNED by this provider
            $this->em->createQuery(
                'DELETE FROM App\Entity\Booking b WHERE b.service IN (
                    SELECT s.id FROM App\Entity\Service s WHERE s.provider = :user
                )'
            )->setParameter('user', $user)->execute();

            // Delete all services owned by this provider
            $this->em->createQuery(
                'DELETE FROM App\Entity\Service s WHERE s.provider = :user'
            )->setParameter('user', $user)->execute();

            // Detach and remove the user entity
            $this->em->remove($user);
        });

        $this->auditLogger->logUserAction('USER_DELETE', $userId, $userEmail);
    }

    public function toggleUserStatus(User $user): string
    {
        $user->setIsActive(!$user->isActive());
        $this->em->flush();
        $status = $user->isActive() ? 'Activated' : 'Suspended';
        $this->auditLogger->logUserAction('USER_TOGGLE_STATUS', $user->getId(), $user->getEmail(), ['status' => $status]);
        return $status;
    }

    public function verifyUser(User $user): void
    {
        $user->setIsVerified(true);
        $this->em->flush();
        $this->auditLogger->logUserAction('USER_VERIFY', $user->getId(), $user->getEmail());
    }

    public function resetUserPassword(User $user, string $newPassword): void
    {
        $user->setPassword($this->hasher->hashPassword($user, $newPassword));
        $this->em->flush();
        $this->auditLogger->logUserAction('USER_PASSWORD_RESET', $user->getId(), $user->getEmail());
    }

    public function deleteService(Service $service): void
    {
        $this->em->wrapInTransaction(function () use ($service): void {
            // Bulk-delete all bookings for this service to avoid N+1
            $this->em->createQuery(
                'DELETE FROM App\Entity\Booking b WHERE b.service = :service'
            )->setParameter('service', $service)->execute();

            $this->em->remove($service);
        });
    }

    public function toggleServicePremium(Service $service): string
    {
        $service->setIsPremium(!$service->isPremium());
        $this->em->flush();
        return $service->isPremium() ? 'Premium' : 'Standard';
    }

    public function toggleServiceStatus(Service $service): string
    {
        $service->setIsActive(!$service->isActive());
        $this->em->flush();
        return $service->isActive() ? 'Visible' : 'Hidden';
    }

    /**
     * Updates a booking status, validating against the canonical list in AppConstants.
     *
     * @return bool true if the status was updated, false if the value was invalid
     */
    public function updateBookingStatus(Booking $booking, string $newStatus): bool
    {
        if (!in_array($newStatus, AppConstants::BOOKING_STATUSES, true)) {
            return false;
        }

        $oldStatus = $booking->getStatus();
        $booking->setStatus($newStatus);
        $this->em->flush();

        $this->auditLogger->logUserAction(
            'BOOKING_STATUS_UPDATE',
            $booking->getId(),
            (string) $booking->getId(),
            ['old_status' => $oldStatus, 'new_status' => $newStatus]
        );

        return true;
    }

    public function deleteBooking(Booking $booking): void
    {
        $this->em->remove($booking);
        $this->em->flush();
    }
}
