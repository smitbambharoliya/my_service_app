<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\User;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/notifications')]
#[IsGranted('ROLE_USER')]
class NotificationController extends AbstractController
{
    #[Route('', name: 'app_notification_index', methods: ['GET'])]
    public function index(NotificationRepository $notificationRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('notification/index.html.twig', [
            'notifications' => $notificationRepository->findBy(
                ['user' => $user],
                ['createdAt' => 'DESC'],
                50
            ),
            'unreadCount' => $notificationRepository->countUnreadForUser($user),
        ]);
    }

    #[Route('/summary', name: 'app_notification_summary', methods: ['GET'])]
    public function summary(NotificationRepository $notificationRepository): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $notifications = array_map(function (Notification $notification): array {
            return [
                'id' => $notification->getId(),
                'type' => $notification->getType(),
                'title' => $notification->getTitle(),
                'message' => $notification->getMessage(),
                'isRead' => $notification->isRead(),
                'createdAt' => $notification->getCreatedAt()?->format('M d, H:i'),
                'visitUrl' => $this->generateUrl('app_notification_visit', ['id' => $notification->getId()]),
            ];
        }, $notificationRepository->findRecentForUser($user, 6));

        return $this->json([
            'count' => $notificationRepository->countUnreadForUser($user),
            'notifications' => $notifications,
        ]);
    }

    #[Route('/mark-all-read', name: 'app_notification_mark_all_read', methods: ['POST'])]
    public function markAllRead(Request $request, NotificationRepository $notificationRepository): Response
    {
        if (!$this->isCsrfTokenValid('mark_all_notifications', $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token.');

            return $this->redirectToRoute('app_notification_index');
        }

        /** @var User $user */
        $user = $this->getUser();
        $notificationRepository->markAllAsReadForUser($user);

        $this->addFlash('success', 'All notifications have been marked as read.');

        return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('app_notification_index'));
    }

    #[Route('/{id}/visit', name: 'app_notification_visit', methods: ['GET'])]
    public function visit(Notification $notification, EntityManagerInterface $entityManager): Response
    {
        if ($notification->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if (!$notification->isRead()) {
            $notification->setIsRead(true);
            $entityManager->flush();
        }

        if ($notification->getUrl()) {
            return $this->redirect($notification->getUrl());
        }

        return $this->redirectToRoute('app_notification_index');
    }
}
