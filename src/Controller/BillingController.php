<?php

namespace App\Controller;

use App\Entity\Billing;
use App\Entity\Service;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')] 
class BillingController extends AbstractController
{

    
    #[Route('/billing', name: 'app_billing_history')]
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
        $billing->setAmount(999.00); // Premium Plan Price
        $billing->setPaymentStatus('success');
        $billing->setTransactionId('TXN-' . strtoupper(bin2hex(random_bytes(4))));
        $billing->setCreatedAt(new \DateTimeImmutable());

        $entityManager->persist($billing);

 
        $services = $entityManager->getRepository(Service::class)->findBy(['provider' => $user]);
        foreach ($services as $service) {
            $service->setIsPremium(true);
        }

        $entityManager->flush();

        $this->addFlash('success', 'Badhai ho! Have tame Premium Member cho. Tamari services search ma top par dekhase.');

        return $this->redirectToRoute('app_billing_history');
    }
}