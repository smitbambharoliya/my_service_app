<?php

namespace App\Security\Voter;

use App\Entity\Booking;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class BookingVoter extends Voter
{
    public const VIEW = 'view';
    public const EDIT = 'edit';
    public const CANCEL = 'cancel';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::CANCEL])
            && $subject instanceof Booking;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // If the user is not authenticated, deny access
        if (!$user instanceof User) {
            return false;
        }

        /** @var Booking $booking */
        $booking = $subject;

        return match ($attribute) {
            self::VIEW => $this->canView($booking, $user),
            self::EDIT => $this->canEdit($booking, $user),
            self::CANCEL => $this->canCancel($booking, $user),
            default => false,
        };
    }

    private function canView(Booking $booking, User $user): bool
    {
        // Customer can view their own bookings
        if ($booking->getCustomer() === $user) {
            return true;
        }

        // Provider can view bookings for their services
        if ($booking->getService()?->getProvider() === $user) {
            return true;
        }

        // Admin can view all bookings (if needed, add role check here)
        return false;
    }

    private function canEdit(Booking $booking, User $user): bool
    {
        // Only provider can edit status
        return $booking->getService()?->getProvider() === $user
            && in_array($booking->getStatus(), ['pending', 'confirmed']);
    }

    private function canCancel(Booking $booking, User $user): bool
    {
        // Customer can cancel if not completed
        if ($booking->getCustomer() === $user) {
            return $booking->getStatus() !== 'completed';
        }

        // Provider can cancel if not started
        if ($booking->getService()?->getProvider() === $user) {
            return $booking->getStatus() !== 'on-the-way' && $booking->getStatus() !== 'completed';
        }

        return false;
    }
}
