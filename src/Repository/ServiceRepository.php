<?php

namespace App\Repository;

use App\Constants\AppConstants;
use App\Entity\Service;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Service>
 */
class ServiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Service::class);
    }

    /**
     * Find active services with eager-loaded relations to avoid N+1 queries
     */
    public function findActiveWithProvider(int $limit = null): array
    {
        $qb = $this->createQueryBuilder('s')
            ->innerJoin('s.provider', 'p')
            ->addSelect('p')
            ->innerJoin('s.category', 'c')
            ->addSelect('c')
            ->where('s.isActive = true')
            ->orderBy('s.id', 'DESC');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    public function smartMatchSearch(?string $query, ?string $category, ?string $priceRange, ?string $tier = null, ?string $isPremium = null): array
    {
        $qb = $this->createQueryBuilder('s')
            ->innerJoin('s.provider', 'p')
            ->addSelect('p')
            ->innerJoin('s.category', 'c')
            ->addSelect('c')
            ->where('s.isActive = true');

        if ($query) {
            $qb->andWhere('s.title LIKE :query OR s.description LIKE :query OR c.name LIKE :query')
               ->setParameter('query', '%' . $query . '%');
        }

        if ($category && $category !== 'All Services') {
            $qb->andWhere('c.name = :cat')
               ->setParameter('cat', $category);
        }

        if ($priceRange) {
            match ($priceRange) {
                'Under ₹1,000' => $qb->andWhere('s.price < 1000'),
                '₹1,000 - ₹5,000' => $qb->andWhere('s.price >= 1000 AND s.price <= 5000'),
                '₹5,000+' => $qb->andWhere('s.price > 5000'),
                default => null,
            };
        }

        if ($tier) {
            $qb->andWhere('p.tier = :tier')
               ->setParameter('tier', $tier);
        }

        if ($isPremium !== null && $isPremium !== '') {
            $qb->andWhere('s.isPremium = :isPremium')
               ->setParameter('isPremium', $isPremium === '1');
        }

        return $qb->orderBy('s.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
