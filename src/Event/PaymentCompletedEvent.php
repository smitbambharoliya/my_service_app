<?php

namespace App\Event;

use App\Entity\Billing;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched when a Stripe payment is confirmed via webhook.
 */
class PaymentCompletedEvent extends Event
{
    public const NAME = 'payment.completed';

    public function __construct(
        private Billing $billing,
    ) {
    }

    public function getBilling(): Billing
    {
        return $this->billing;
    }
}
