<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class NotificationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private string $fromAddress = 'no-reply@servicehub.local',
    ) {
    }

    public function notifyBookingUpdate(User $recipient, string $title, string $message, ?string $url = null): void
    {
        $this->dispatch($recipient, 'booking', $title, $message, $url);
    }

    public function notifyMessage(User $recipient, string $title, string $message, ?string $url = null): void
    {
        $this->dispatch($recipient, 'message', $title, $message, $url);
    }

    private function dispatch(User $recipient, string $type, string $title, string $message, ?string $url = null): void
    {
        $shouldPersist = $this->shouldCreateInApp($recipient, $type);

        if ($shouldPersist) {
            $notification = (new Notification())
                ->setUser($recipient)
                ->setType($type)
                ->setTitle($title)
                ->setMessage($message)
                ->setUrl($url);

            $this->entityManager->persist($notification);
        }

        if ($this->shouldSendEmail($recipient, $type)) {
            $this->sendEmail($recipient, $title, $message, $url);
        }

        if ($shouldPersist) {
            $this->entityManager->flush();
        }
    }

    private function shouldCreateInApp(User $recipient, string $type): bool
    {
        return match ($type) {
            'message' => $recipient->isMessageInAppNotifications(),
            default => $recipient->isBookingInAppNotifications(),
        };
    }

    private function shouldSendEmail(User $recipient, string $type): bool
    {
        return match ($type) {
            'message' => $recipient->isMessageEmailNotifications(),
            default => $recipient->isBookingEmailNotifications(),
        };
    }

    private function sendEmail(User $recipient, string $title, string $message, ?string $url = null): void
    {
        $bodyLines = [
            $message,
        ];

        if ($url) {
            $bodyLines[] = '';
            $bodyLines[] = 'Open in ServiceHub: ' . $url;
        }

        $email = (new Email())
            ->from(new Address($this->fromAddress, 'ServiceHub'))
            ->to($recipient->getEmail())
            ->subject($title)
            ->text(implode("\n", $bodyLines));

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $exception) {
            $this->logger->warning('Notification email failed to send.', [
                'recipient' => $recipient->getEmail(),
                'title' => $title,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
