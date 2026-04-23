<?php

namespace App\Event;

use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched after a new user completes registration.
 * Carries the OTP code so listeners can send the verification email.
 */
class UserRegisteredEvent extends Event
{
    public const NAME = 'user.registered';

    public function __construct(
        private User $user,
        private string $otpCode,
    ) {
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getOtpCode(): string
    {
        return $this->otpCode;
    }
}
