<?php

namespace App\Service;

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
        if (!in_array('ROLE_ADMIN', $roles)) {
            $roles[] = 'ROLE_ADMIN';
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
        if (!in_array('ROLE_PROVIDER', $roles)) {
            $roles[] = 'ROLE_PROVIDER';
            $user->setRoles($roles);
            $this->em->flush();
            $this->auditLogger->logUserAction('USER_PROMOTE_PROVIDER', $user->getId(), $user->getEmail());
            return true;
        }
        return false;
    }

    public function demoteFromProvider(User $user): bool
    {
        $roles = array_diff($user->getRoles(), ['ROLE_PROVIDER']);
        $user->setRoles(array_values($roles));
        $this->em->flush();
        $this->auditLogger->logUserAction('USER_DEMOTE_PROVIDER', $user->getId(), $user->getEmail());
        return true;
    }

    public function deleteUser(User $user): void
    {
        foreach ($user->getBookings() as $booking) {
            $this->em->remove($booking);
        }
        foreach ($user->getServices() as $service) {
            foreach ($service->getBookings() as $booking) {
                $this->em->remove($booking);
            }
            $this->em->remove($service);
        }

        $this->auditLogger->logUserAction('USER_DELETE', $user->getId(), $user->getEmail());
        $this->em->remove($user);
        $this->em->flush();
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
        foreach ($service->getBookings() as $booking) {
            $this->em->remove($booking);
        }
        $this->em->remove($service);
        $this->em->flush();
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

    public function updateBookingStatus(Booking $booking, string $newStatus): void
    {
        $allowed = ['pending', 'confirmed', 'completed'];
        if (in_array($newStatus, $allowed)) {
            $booking->setStatus($newStatus);
            $this->em->flush();
        }
    }

    public function deleteBooking(Booking $booking): void
    {
        $this->em->remove($booking);
        $this->em->flush();
    }
}
