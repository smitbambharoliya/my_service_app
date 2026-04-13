<?php

namespace App\Repository;

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

    public function smartMatchSearch(?string $query, ?string $category, ?string $priceRange, ?string $tier = null, ?string $isPremium = null): array
    {
        $qb = $this->createQueryBuilder('s')
            ->where('s.isActive = true');

        if ($query) {
            $qb->andWhere('s.title LIKE :query OR s.description LIKE :query OR s.category LIKE :query')
               ->setParameter('query', '%' . $query . '%');
        }

        if ($category && $category !== 'All Services') {
            $qb->andWhere('s.category = :cat')
               ->setParameter('cat', $category);
        }

        if ($priceRange) {
            if ($priceRange === 'Under ₹1,000') {
                $qb->andWhere('s.price < 1000');
            } elseif ($priceRange === '₹1,000 - ₹5,000') {
                $qb->andWhere('s.price >= 1000 AND s.price <= 5000');
            } elseif ($priceRange === '₹5,000+') {
                $qb->andWhere('s.price > 5000');
            }
        }

        if ($tier) {
            $qb->join('s.provider', 'p')
               ->andWhere('p.tier = :tier')
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
