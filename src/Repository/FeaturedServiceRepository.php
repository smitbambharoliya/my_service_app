<?php

namespace App\Repository;

use App\Entity\FeaturedService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FeaturedService>
 */
class FeaturedServiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeaturedService::class);
    }

    /**
     * Fetch active featured services ordered by section and display order.
     */
    public function findActiveFeaturedServices(): array
    {
        $now = new \DateTime();

        return $this->createQueryBuilder('f')
            ->innerJoin('f.service', 's')
            ->addSelect('s')
            ->where('f.isActive = true')
            ->andWhere('f.startDate IS NULL OR f.startDate <= :now')
            ->andWhere('f.endDate IS NULL OR f.endDate >= :now')
            ->setParameter('now', $now)
            ->orderBy('f.section', 'ASC')
            ->addOrderBy('f.displayOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
