<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /**
     * @return Category[] Returns an array of active Category objects ordered by sort order
     */
    public function findActiveOrdered(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('c.sortOrder', 'ASC')
            ->addOrderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find category by slug
     */
    public function findBySlug(string $slug): ?Category
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get all categories with service count
     * @return array<array{category: Category, serviceCount: int}>
     */
    public function findAllWithServiceCount(): array
    {
        return $this->createQueryBuilder('c')
            ->select('c', 'COUNT(s.id) as serviceCount')
            ->leftJoin('c.services', 's')
            ->groupBy('c.id')
            ->orderBy('c.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
