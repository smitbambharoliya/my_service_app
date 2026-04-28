<?php

namespace App\Repository;

use App\Entity\Voucher;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Voucher>
 */
class VoucherRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Voucher::class);
    }

    public function findValidByCode(string $code): ?Voucher
    {
        $now = new \DateTimeImmutable();

        return $this->createQueryBuilder('v')
            ->where('v.code = :code')
            ->andWhere('v.isActive = true')
            ->andWhere('v.startDate <= :now')
            ->andWhere('v.endDate >= :now')
            ->andWhere('v.usageCount < v.usageLimit')
            ->setParameter('code', $code)
            ->setParameter('now', $now)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findActiveForFeaturedService(int $featuredServiceId): array
    {
        $now = new \DateTimeImmutable();

        return $this->createQueryBuilder('v')
            ->where('v.featuredService = :fsId')
            ->andWhere('v.isActive = true')
            ->andWhere('v.startDate <= :now')
            ->andWhere('v.endDate >= :now')
            ->andWhere('v.usageCount < v.usageLimit')
            ->setParameter('fsId', $featuredServiceId)
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();
    }
}
