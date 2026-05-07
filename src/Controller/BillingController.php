<?php

namespace App\Controller;

use App\Entity\Billing;
use App\Entity\Booking;
use App\Entity\Service;
use App\Entity\User;
use App\Event\PaymentCompletedEvent;
use App\Service\BillingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[IsGranted('ROLE_USER')]
class BillingController extends AbstractController
{


    #[Route('/billing', name: 'app_billing_index')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $billings = $entityManager->getRepository(Billing::class)->findBy(['user' => $user], ['createdAt' => 'DESC']);

        return $this->render('billing/index.html.twig', [
            'billings' => $billings,
        ]);
    }

    #[Route('/billing/history', name: 'app_billing_history')]
    public function history(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();


        $billings = $entityManager->getRepository(Billing::class)->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC']
        );


        $totalSpent = 0;
        foreach ($billings as $bill) {
            if ($bill->getPaymentStatus() === 'success') {
                $totalSpent += $bill->getAmount();
            }
        }

        return $this->render('billing/history.html.twig', [
            'billings' => $billings,
            'total_spent' => $totalSpent,
        ]);
    }

    #[Route('/billing/upgrade-premium', name: 'app_billing_upgrade')]
    #[IsGranted('ROLE_PROVIDER')]
    public function upgrade(BillingService $billingService): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $billing = $billingService->createPremiumUpgrade($user, (string) ($this->getParameter('premium_plan_price') ?? '999.00'));

        $this->addFlash('info', 'Redirecting to secure gateway to complete your premium upgrade.');

        return $this->redirectToRoute('app_billing_checkout', ['id' => $billing->getId()]);
    }

    #[Route('/dashboard/provider/billing/new', name: 'app_provider_billing_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_PROVIDER')]
    public function newBilling(Request $request, EntityManagerInterface $entityManager, BillingService $billingService): Response
    {
        /** @var User $provider */
        $provider = $this->getUser();

        $bookings = $entityManager->getRepository(Booking::class)->createQueryBuilder('b')
            ->addSelect('c')
            ->join('b.service', 's')
            ->join('b.customer', 'c')
            ->where('s.provider = :provider')
            ->setParameter('provider', $provider)
            ->getQuery()
            ->getResult();

        $customers = [];
        foreach ($bookings as $b) {
            $customer = $b->getCustomer();
            if ($customer) {
                $customers[$customer->getId()] = $customer;
            }
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('create_bill', $request->request->get('_token'))) {
                $this->addFlash('danger', 'Invalid CSRF token.');
                return $this->redirectToRoute('app_provider_billing_new');
            }

            $customerId = $request->request->get('customer_id');
            $category = $request->request->get('category');
            $description = $request->request->get('description');

            $itemNames = $request->request->all('item_name');
            $itemAmounts = $request->request->all('item_amount');

            $totalAmount = 0.0;
            $items = [];

            if (is_array($itemNames) && is_array($itemAmounts)) {
                foreach ($itemNames as $index => $name) {
                    $amt = isset($itemAmounts[$index]) ? (float)$itemAmounts[$index] : 0.0;
                    if (!empty($name) && $amt > 0) {
                        $items[] = [
                            'name' => $name,
                            'amount' => $amt
                        ];
                        $totalAmount += $amt;
                    }
                }
            }

            if ($customerId && $totalAmount > 0) {
                $customer = array_key_exists($customerId, $customers) ? $customers[$customerId] : null;

                if ($customer) {
                    $billingService->createCustomBill($customer, $category, $description, $items, $totalAmount);

                    $this->addFlash('success', 'Custom bill of ₹' . $totalAmount . ' generated successfully for ' . $customer->getFullName() . '.');
                    return $this->redirectToRoute('app_provider_billing_new');
                } else {
                    $this->addFlash('danger', 'Selected customer is invalid or has not booked you.');
                }
            } else {
                $this->addFlash('danger', 'Please add at least one valid service item and select a customer.');
            }
        }

        return $this->render('dashboard/provider_billing_new.html.twig', [
            'customers' => $customers,
        ]);
    }

    #[Route('/billing/checkout/{id}', name: 'app_billing_checkout')]
    public function checkout(Billing $billing, \App\Service\StripeService $stripeService, EntityManagerInterface $em): Response
    {
        if ($billing->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($billing->getPaymentStatus() === 'paid') {
            $this->addFlash('info', 'This protocol has already been settled.');
            return $this->redirectToRoute('app_billing_index');
        }

        try {
            $session = $stripeService->createCheckoutSession($billing);
            $billing->setStripeSessionId($session->id);
            $em->flush();

            return $this->redirect($session->url, 303);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Payment gateway error. Please try again or contact support.');
            return $this->redirectToRoute('app_billing_index');
        }
    }

    #[Route('/billing/success/{id}', name: 'app_payment_success')]
    public function paymentSuccess(Billing $billing, EntityManagerInterface $em): Response
    {
        // Now depends on webhook. If still unpaid, show processing.
        if ($billing->getPaymentStatus() !== 'paid') {
            $this->addFlash('info', 'Your payment is processing. Once verified by our gateway, your invoice will be generated.');
        } else {
            $this->addFlash('success', 'Protocol successfully settled. Payment confirmed via Stripe secure gateway.');
        }
        
        return $this->redirectToRoute('app_billing_index');
    }

    #[Route('/billing/cancel/{id}', name: 'app_payment_cancel')]
    public function paymentCancel(Billing $billing): Response
    {
        $this->addFlash('warning', 'Payment protocol interrupted. Transaction was not completed.');
        return $this->redirectToRoute('app_billing_index');
    }

    #[Route('/webhook/stripe', name: 'app_stripe_webhook', methods: ['POST'])]
    public function stripeWebhook(Request $request, EntityManagerInterface $em, EventDispatcherInterface $dispatcher): Response
    {
        $payload          = $request->getContent();
        $sig_header       = $request->headers->get('stripe-signature');
        $endpoint_secret  = $this->getParameter('stripe_webhook_secret');

        if (!$endpoint_secret) {
            return new Response('Stripe webhook secret is missing from environment.', 500);
        }

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload, $sig_header, $endpoint_secret
            );
        } catch(\UnexpectedValueException|\Stripe\Exception\SignatureVerificationException $e) {
            return new Response('Invalid payload or signature', 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            $billing = $em->getRepository(Billing::class)->findOneBy(['stripeSessionId' => $session->id]);

            if ($billing && $billing->getPaymentStatus() !== 'paid') {
                $billing->setPaymentStatus('paid');
                $billing->setTransactionId('STR-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8)));

                // Dispatch event — listeners handle premium upgrade + any post-payment logic
                $dispatcher->dispatch(new PaymentCompletedEvent($billing));

                $em->flush();
            }
        }

        return new Response('Webhook Handled', 200);
    }

    #[Route('/billing/download/{id}', name: 'app_billing_download')]
    public function downloadInvoice(Billing $billing, \Knp\Bundle\SnappyBundle\Snappy\Response\PdfResponse $pdfResponse, \Knp\Snappy\Pdf $pdf): Response
    {
        /** @var \App\Entity\User $currentUser */
        $currentUser = $this->getUser();

        // Access allowed for the billing's own user or an admin.
        // (Billing has no direct Booking relation, so provider access is scoped to their own bills.)
        $isOwner = $billing->getUser() === $currentUser;
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        if (!$isOwner && !$isAdmin) {
            throw $this->createAccessDeniedException();
        }

        $html = $this->renderView('billing/invoice_pdf.html.twig', [
            'billing' => $billing,
        ]);

        return new \Knp\Bundle\SnappyBundle\Snappy\Response\PdfResponse(
            $pdf->getOutputFromHtml($html),
            'ServiceHub_Invoice_' . $billing->getTransactionId() . '.pdf'
        );
    }
}
