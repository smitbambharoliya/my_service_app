<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Get provider statistics efficiently (avoiding N+1 queries)
     */
    public function getProviderStats(User $provider): array
    {
        $em = $this->getEntityManager();

        // Get completed jobs count
        $completedJobsCount = $em->createQueryBuilder()
            ->select('COUNT(b.id)')
            ->from(\App\Entity\User::class, 'u')
            ->join('u.services', 's')
            ->join('s.bookings', 'b')
            ->where('u.id = :userId')
            ->andWhere('b.status = :status')
            ->setParameter('userId', $provider->getId())
            ->setParameter('status', 'completed')
            ->getQuery()
            ->getSingleScalarResult();

        // Get average rating
        $avgRating = $em->createQueryBuilder()
            ->select('AVG(r.rating)')
            ->from(\App\Entity\User::class, 'u')
            ->join('u.reviewsReceived', 'r')
            ->where('u.id = :userId')
            ->setParameter('userId', $provider->getId())
            ->getQuery()
            ->getSingleScalarResult();

        // Get review count
        $reviewCount = $em->createQueryBuilder()
            ->select('COUNT(r.id)')
            ->from(\App\Entity\User::class, 'u')
            ->join('u.reviewsReceived', 'r')
            ->where('u.id = :userId')
            ->setParameter('userId', $provider->getId())
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'completed_jobs' => (int)$completedJobsCount,
            'average_rating' => $avgRating ? round($avgRating, 1) : null,
            'review_count' => (int)$reviewCount,
        ];
    }

    /**
     * Get provider's active services (excludes given service)
     */
    public function getProviderActiveServices(User $provider, ?\App\Entity\Service $excludeService = null): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('s')
            ->from(\App\Entity\Service::class, 's')
            ->where('s.provider = :provider')
            ->andWhere('s.isActive = true')
            ->setParameter('provider', $provider)
            ->orderBy('s.id', 'DESC')
            ->setMaxResults(3);

        if ($excludeService !== null) {
            $qb->andWhere('s.id != :excludeId')
               ->setParameter('excludeId', $excludeService->getId());
        }

        return $qb->getQuery()->getResult();
    }

    //    /**
    //     * @return User[] Returns an array of User objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?User
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
