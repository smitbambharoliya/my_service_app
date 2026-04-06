<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\User;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/messages')]
#[IsGranted('ROLE_USER')]
class MessageController extends AbstractController
{
    #[Route('/', name: 'app_message_inbox', methods: ['GET'])]
    public function inbox(MessageRepository $messageRepo, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        // Custom query we built in MessageRepository to get latest contacts
        $contacts = $messageRepo->getContactsForUser($user);

        return $this->render('message/inbox.html.twig', [
            'contacts' => $contacts,
        ]);
    }

    #[Route('/chat/{id}', name: 'app_message_chat', methods: ['GET', 'POST'])]
    public function chat(User $contact, Request $request, MessageRepository $messageRepo, EntityManagerInterface $em): Response
    {
        $currentUser = $this->getUser();

        // Mark unread messages as read
        $unreadMessages = $messageRepo->findBy([
            'sender' => $contact,
            'receiver' => $currentUser,
            'isRead' => false
        ]);

        foreach ($unreadMessages as $msg) {
            $msg->setIsRead(true);
        }
        if (count($unreadMessages) > 0) {
            $em->flush();
        }

        // Handle quick POST sending from standard form fallback
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('send_message' . $contact->getId(), $request->request->get('_token'))) {
                $this->addFlash('danger', 'Invalid CSRF token. Please try again.');
                return $this->redirectToRoute('app_message_chat', ['id' => $contact->getId()]);
            }
            $content = $request->request->get('content');
            if ($content) {
                $msg = new Message();
                $msg->setSender($currentUser);
                $msg->setReceiver($contact);
                $msg->setContent($content);
                $em->persist($msg);
                $em->flush();
            }
            return $this->redirectToRoute('app_message_chat', ['id' => $contact->getId()]);
        }

        $conversation = $messageRepo->getConversation($currentUser, $contact);

        return $this->render('message/chat.html.twig', [
            'contact' => $contact,
            'conversation' => $conversation
        ]);
    }

    // ── AJAX POLLING API ──
    #[Route('/api/poll/{id}', name: 'api_message_poll', methods: ['GET'])]
    public function apiPoll(User $contact, MessageRepository $messageRepo): JsonResponse
    {
        $currentUser = $this->getUser();
        $conversation = $messageRepo->getConversation($currentUser, $contact);

        $messagesFormat = [];
        foreach ($conversation as $msg) {
            $messagesFormat[] = [
                'id' => $msg->getId(),
                'senderId' => $msg->getSender()->getId(),
                'content' => $msg->getContent(),
                'time' => $msg->getCreatedAt()->format('H:i'),
                'isRead' => $msg->isRead()
            ];
        }

        return $this->json([
            'messages' => $messagesFormat
        ]);
    }

    #[Route('/api/send/{id}', name: 'api_message_send', methods: ['POST'])]
    public function apiSend(User $contact, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $currentUser = $this->getUser();

        if (!$this->isCsrfTokenValid('send_message' . $contact->getId(), $request->headers->get('X-CSRF-TOKEN'))) {
            return $this->json(['error' => 'Invalid CSRF token'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $content = current($data) ?: $request->request->get('content'); // handle raw json or standard payload

        if (!$content) {
            return $this->json(['status' => 'error', 'message' => 'Empty content'], 400);
        }

        $msg = new Message();
        $msg->setSender($currentUser);
        $msg->setReceiver($contact);
        $msg->setContent($content);
        $em->persist($msg);
        $em->flush();

        return $this->json(['status' => 'success']);
    }

    #[Route('/api/unread-count', name: 'api_message_unread_count', methods: ['GET'])]
    public function unreadCount(MessageRepository $messageRepo): JsonResponse
    {
        $currentUser = $this->getUser();
        if (!$currentUser) return $this->json(['count' => 0]);

        $count = $messageRepo->count(['receiver' => $currentUser, 'isRead' => false]);
        return $this->json(['count' => $count]);
    }
}
