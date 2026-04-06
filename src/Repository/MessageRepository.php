<?php

namespace App\Repository;

use App\Entity\Message;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Message>
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /**
     * Get unique contacts a user has messaged with, along with their last message
     */
    public function getContactsForUser(User $user): array
    {
        $conn = $this->getEntityManager()->getConnection();

        // Raw SQL for performance to get the latest message per conversation
        $sql = '
            SELECT 
                u.id as contact_id, 
                u.email as contact_email, 
                m.content as last_message, 
                m.created_at as last_message_date,
                (SELECT COUNT(id) FROM message unread WHERE unread.sender_id = u.id AND unread.receiver_id = :user_id AND unread.is_read = 0) as unread_count
            FROM user u
            INNER JOIN (
                SELECT 
                    CASE 
                        WHEN sender_id = :user_id THEN receiver_id 
                        ELSE sender_id 
                    END as other_user_id,
                    MAX(created_at) as latest_msg_time
                FROM message
                WHERE sender_id = :user_id OR receiver_id = :user_id
                GROUP BY other_user_id
            ) latest ON u.id = latest.other_user_id
            INNER JOIN message m ON 
                (m.sender_id = u.id AND m.receiver_id = :user_id AND m.created_at = latest.latest_msg_time) OR 
                (m.sender_id = :user_id AND m.receiver_id = u.id AND m.created_at = latest.latest_msg_time)
            ORDER BY m.created_at DESC
        ';

        $resultSet = $conn->executeQuery($sql, ['user_id' => $user->getId()]);

        return $resultSet->fetchAllAssociative();
    }

    /**
     * Get conversation between two users
     */
    public function getConversation(User $user1, User $user2): array
    {
        return $this->createQueryBuilder('m')
            ->where('(m.sender = :u1 AND m.receiver = :u2) OR (m.sender = :u2 AND m.receiver = :u1)')
            ->setParameter('u1', $user1)
            ->setParameter('u2', $user2)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
