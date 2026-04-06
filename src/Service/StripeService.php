<?php

namespace App\Service;

use App\Entity\Billing;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class StripeService
{
    private string $secretKey;

    public function __construct(string $stripeSecretKey, private UrlGeneratorInterface $urlGenerator)
    {
        $this->secretKey = $stripeSecretKey;
        Stripe::setApiKey($this->secretKey);
    }

    public function createCheckoutSession(Billing $billing): Session
    {
        return Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'inr',
                    'product_data' => [
                        'name' => $billing->getServiceName() ?? 'ServiceHub Protocol Fee',
                        'description' => $billing->getDescription() ?? 'Secure payment for ServiceHub operation.',
                    ],
                    'unit_amount' => (int)($billing->getAmount() * 100),
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $this->urlGenerator->generate('app_payment_success', [
                'id' => $billing->getId()
            ], UrlGeneratorInterface::ABSOLUTE_URL) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $this->urlGenerator->generate('app_payment_cancel', [
                'id' => $billing->getId()
            ], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);
    }
}
