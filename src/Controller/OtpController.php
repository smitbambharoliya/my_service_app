<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

class OtpController extends AbstractController
{
    #[Route('/verify-otp', name: 'app_verify_otp')]
    public function verify(Request $request, EntityManagerInterface $entityManager, Security $security): Response
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

            // Check if OTP is expired
            if ($user->isOtpExpired()) {
                $this->addFlash('error', 'Your OTP has expired. Please request a new one.');
                return $this->redirectToRoute('app_resend_otp');
            }

            // Check if max attempts exceeded
            $maxAttempts = (int)($_ENV['OTP_MAX_ATTEMPTS'] ?? 5);
            if ($user->getOtpAttempts() >= $maxAttempts) {
                $this->addFlash('error', 'Maximum OTP verification attempts exceeded. Please request a new OTP.');
                $request->getSession()->remove('verify_email');
                return $this->redirectToRoute('app_register');
            }

            if ($user->getOtpCode() === $submittedOtp) {
                $user->setIsVerified(true);
                $user->setOtpCode(null);
                $user->resetOtpAttempts();
                $user->setOtpExpiresAt(null);
                $entityManager->flush();

                $request->getSession()->remove('verify_email');

                $this->addFlash('success', 'Email verified! Welcome to ServiceHub.');

                $response = $security->login($user);

                return $response ?? $this->redirectToRoute('app_home');
            }

            // Increment failed attempts
            $user->incrementOtpAttempts();
            $entityManager->flush();

            $remaining = $maxAttempts - $user->getOtpAttempts();
            if ($remaining > 0) {
                $this->addFlash('error', "Invalid OTP. {$remaining} attempt(s) remaining.");
            } else {
                $this->addFlash('error', 'Maximum OTP verification attempts exceeded. Please request a new OTP.');
                $request->getSession()->remove('verify_email');
                return $this->redirectToRoute('app_register');
            }
        }

        return $this->render('registration/verify_otp.html.twig', [
            'email' => $email
        ]);
    }

    #[Route('/resend-otp', name: 'app_resend_otp')]
    public function resend(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        $emailAddress = $request->getSession()->get('verify_email');

        if (!$emailAddress) {
            $this->addFlash('error', 'Session expired. Please register again.');
            return $this->redirectToRoute('app_register');
        }

        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $emailAddress]);

        if ($user) {
            $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $user->setOtpCode($otp);
            $user->resetOtpAttempts(); // Reset failed attempts on resend
            $expiryMinutes = (int)($_ENV['OTP_EXPIRY_MINUTES'] ?? 10);
            $user->setOtpExpiresAt(new \DateTimeImmutable("+ {$expiryMinutes} minutes"));
            $entityManager->flush();

            // Send new OTP via email
            try {
                $email = (new Email())
                    ->from($_ENV['MAILER_FROM'] ?? 'noreply@servicehub.local')
                    ->to($emailAddress)
                    ->subject('Your New ServiceHub Verification Code')
                    ->html($this->renderView('registration/otp_email.html.twig', [
                        'otp' => $otp,
                        'name' => $user->getFullName(),
                    ]));

                $mailer->send($email);
                $this->addFlash('success', 'New OTP sent to ' . $emailAddress . '. Please check your inbox.');
            } catch (\Exception $e) {
                // Fallback
                $this->addFlash('warning', 'Email could not be sent. Your new OTP code is: <strong>' . $otp . '</strong>');
            }
        }

        return $this->redirectToRoute('app_verify_otp');
    }
}
