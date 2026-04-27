<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class OtpService
{
    public function __construct(
        private EntityManagerInterface $em,
        private MailerInterface $mailer,
        private Environment $twig
    ) {
    }

    public function generateAndSendOtp(User $user, string $subject = 'Your ServiceHub Verification Code'): ?string
    {
        $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->setOtpCode($otp);
        $user->resetOtpAttempts();
        
        $expiryMinutes = (int)($_ENV['OTP_EXPIRY_MINUTES'] ?? 10);
        $user->setOtpExpiresAt(new \DateTimeImmutable("+ {$expiryMinutes} minutes"));
        
        $this->em->flush();

        try {
            $email = (new Email())
                ->from($_ENV['MAILER_FROM'] ?? 'noreply@servicehub.local')
                ->to($user->getEmail())
                ->subject($subject)
                ->html($this->twig->render('registration/otp_email.html.twig', [
                    'otp' => $otp,
                    'name' => $user->getFullName(),
                ]));

            $this->mailer->send($email);
            return null; // indicates success without fallback
        } catch (\Exception $e) {
            return $otp; // return OTP for fallback display
        }
    }

    public function verifyOtp(User $user, string $submittedOtp): array
    {
        if ($user->isOtpExpired()) {
            return ['status' => 'expired'];
        }

        $maxAttempts = (int)($_ENV['OTP_MAX_ATTEMPTS'] ?? 5);
        if ($user->getOtpAttempts() >= $maxAttempts) {
            return ['status' => 'max_attempts_exceeded'];
        }

        if ($user->getOtpCode() === $submittedOtp) {
            $user->setIsVerified(true);
            $user->setOtpCode(null);
            $user->resetOtpAttempts();
            $user->setOtpExpiresAt(null);
            $this->em->flush();
            return ['status' => 'success'];
        }

        $user->incrementOtpAttempts();
        $this->em->flush();

        $remaining = $maxAttempts - $user->getOtpAttempts();
        
        if ($remaining > 0) {
            return ['status' => 'invalid', 'remaining' => $remaining];
        } else {
            return ['status' => 'max_attempts_exceeded'];
        }
    }
}
