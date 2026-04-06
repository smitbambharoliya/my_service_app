<?php

namespace App\Controller;

use App\Entity\Billing;
use App\Entity\Booking;
use App\Entity\Service;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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
    public function upgrade(EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();


        $billing = new Billing();
        $billing->setUser($user);
        $billing->setAmount($_ENV['PREMIUM_PLAN_PRICE'] ?? '999.00');
        $billing->setPaymentStatus('success');
        $billing->setTransactionId('TXN-' . strtoupper(bin2hex(random_bytes(8))) . '-' . time());
        $billing->setCreatedAt(new \DateTimeImmutable());
        $billing->setCategory('Subscription');
        $billing->setServiceName('Premium Plan Upgrade');
        $billing->setDescription('One-time fee to elevate all services to premium tier search placement.');

        $entityManager->persist($billing);


        $services = $entityManager->getRepository(Service::class)->findBy(['provider' => $user]);
        foreach ($services as $service) {
            $service->setIsPremium(true);
        }

        $entityManager->flush();

        $this->addFlash('success', 'Badhai ho! Have tame Premium Member cho. Tamari services search ma top par dekhase.');

        return $this->redirectToRoute('app_billing_history');
    }

    #[Route('/dashboard/provider/billing/new', name: 'app_provider_billing_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_PROVIDER')]
    public function newBilling(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $provider */
        $provider = $this->getUser();

        $bookings = $entityManager->getRepository(Booking::class)->createQueryBuilder('b')
            ->join('b.service', 's')
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

                    $entityManager->persist($billing);
                    $entityManager->flush();

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
            $this->addFlash('danger', 'Stripe Gateway Error: ' . $e->getMessage());
            return $this->redirectToRoute('app_billing_index');
        }
    }

    #[Route('/billing/success/{id}', name: 'app_payment_success')]
    public function paymentSuccess(Billing $billing, EntityManagerInterface $em): Response
    {
        $billing->setPaymentStatus('paid');
        $billing->setTransactionId('STR-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8)));
        $em->flush();

        $this->addFlash('success', 'Protocol successfully settled. Payment confirmed via Stripe secure gateway.');
        return $this->redirectToRoute('app_billing_index');
    }

    #[Route('/billing/cancel/{id}', name: 'app_payment_cancel')]
    public function paymentCancel(Billing $billing): Response
    {
        $this->addFlash('warning', 'Payment protocol interrupted. Transaction was not completed.');
        return $this->redirectToRoute('app_billing_index');
    }

    #[Route('/billing/download/{id}', name: 'app_billing_download')]
    public function downloadInvoice(Billing $billing, \Knp\Bundle\SnappyBundle\Snappy\Response\PdfResponse $pdfResponse, \Knp\Snappy\Pdf $pdf): Response
    {
        if ($billing->getUser() !== $this->getUser() && !$this->isGranted('ROLE_PROVIDER')) {
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
