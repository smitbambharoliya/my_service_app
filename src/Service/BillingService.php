<?php

namespace App\Service;

use App\Entity\Billing;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class BillingService
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
    }

    public function createPremiumUpgrade(User $user, string $price): Billing
    {
        $billing = new Billing();
        $billing->setUser($user);
        $billing->setAmount($price);
        $billing->setPaymentStatus('unpaid');
        $billing->setTransactionId('SUB-' . strtoupper(bin2hex(random_bytes(8))) . '-' . time());
        $billing->setCreatedAt(new \DateTimeImmutable());
        $billing->setCategory('Subscription');
        $billing->setServiceName('Premium Plan Upgrade');
        $billing->setDescription('One-time fee to elevate all services to premium tier search placement.');

        $this->em->persist($billing);
        $this->em->flush();

        return $billing;
    }

    public function createCustomBill(User $customer, string $category, string $description, array $items, float $totalAmount): Billing
    {
        $billing = new Billing();
        $billing->setUser($customer);
        $billing->setAmount((string) $totalAmount);
        $billing->setPaymentStatus('unpaid');
        $billing->setTransactionId('MAN-' . strtoupper(substr(uniqid(), -6)));
        $billing->setCreatedAt(new \DateTimeImmutable());
        $billing->setCategory($category);
        $billing->setServiceName('Custom Bill (' . count($items) . ' items)');
        $billing->setDescription($description);
        $billing->setItems($items);

        $this->em->persist($billing);
        $this->em->flush();

        return $billing;
    }
}
