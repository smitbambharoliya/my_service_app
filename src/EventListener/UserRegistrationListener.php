<?php

namespace App\EventListener;

use App\Event\UserRegisteredEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

/**
 * Sends OTP verification email when a new user registers.
 * If email delivery fails in dev mode, the OTP is stored in the session flash
 * for manual verification.
 */
#[AsEventListener(event: UserRegisteredEvent::class)]
class UserRegistrationListener
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(UserRegisteredEvent $event): void
    {
        $user = $event->getUser();
        $otp = $event->getOtpCode();

        try {
            $html = $this->twig->render('registration/otp_email.html.twig', [
                'otp' => $otp,
                'name' => $user->getFullName(),
            ]);

            $email = (new Email())
                ->from($_ENV['MAILER_FROM'] ?? 'noreply@servicehub.local')
                ->to($user->getEmail())
                ->subject('Your ServiceHub Verification Code')
                ->html($html);

            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            $this->logger->warning('OTP email failed to send during registration.', [
                'user' => $user->getEmail(),
                'error' => $e->getMessage(),
            ]);

            // Re-throw so the controller can handle dev-mode fallback
            throw $e;
        }
    }
}
