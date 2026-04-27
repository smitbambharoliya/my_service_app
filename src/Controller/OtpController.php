<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\OtpService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class OtpController extends AbstractController
{
    #[Route('/verify-otp', name: 'app_verify_otp')]
    public function verify(Request $request, EntityManagerInterface $entityManager, Security $security, OtpService $otpService): Response
    {
        $email = $request->getSession()->get('verify_email');

        if (!$email) {
            $this->addFlash('error', 'Session expired. Please register again.');
            return $this->redirectToRoute('app_register');
        }

        if ($request->isMethod('POST')) {
            $submittedOtp = $request->request->get('otp');
            $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

            if (!$user) {
                $this->addFlash('error', 'User not found. Please register again.');
                return $this->redirectToRoute('app_register');
            }

            $result = $otpService->verifyOtp($user, $submittedOtp);

            if ($result['status'] === 'success') {
                $request->getSession()->remove('verify_email');
                $this->addFlash('success', 'Email verified! Welcome to ServiceHub.');
                $response = $security->login($user);
                return $response ?? $this->redirectToRoute('app_home');
            }

            if ($result['status'] === 'expired') {
                $this->addFlash('error', 'Your OTP has expired. Please request a new one.');
                return $this->redirectToRoute('app_resend_otp');
            }

            if ($result['status'] === 'max_attempts_exceeded') {
                $this->addFlash('error', 'Maximum OTP verification attempts exceeded. Please request a new OTP.');
                $request->getSession()->remove('verify_email');
                return $this->redirectToRoute('app_register');
            }

            if ($result['status'] === 'invalid') {
                $this->addFlash('error', "Invalid OTP. {$result['remaining']} attempt(s) remaining.");
            }
        }

        return $this->render('registration/verify_otp.html.twig', [
            'email' => $email
        ]);
    }

    #[Route('/resend-otp', name: 'app_resend_otp')]
    public function resend(Request $request, EntityManagerInterface $entityManager, OtpService $otpService): Response
    {
        $emailAddress = $request->getSession()->get('verify_email');

        if (!$emailAddress) {
            $this->addFlash('error', 'Session expired. Please register again.');
            return $this->redirectToRoute('app_register');
        }

        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $emailAddress]);

        if ($user) {
            $fallbackOtp = $otpService->generateAndSendOtp($user, 'Your New ServiceHub Verification Code');

            if ($fallbackOtp === null) {
                $this->addFlash('success', 'New OTP sent to ' . $emailAddress . '. Please check your inbox.');
            } else {
                // Fallback if email fails
                $this->addFlash('warning', 'Email could not be sent. Your new OTP code is: <strong>' . $fallbackOtp . '</strong>');
            }
        }

        return $this->redirectToRoute('app_verify_otp');
    }
}
