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

    public function smartMatchSearch(?string $query, ?string $category, ?string $priceRange): array
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

        return $qb->orderBy('s.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
