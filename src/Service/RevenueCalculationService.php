<?php

namespace App\Service;

use App\Constants\AppConstants;
use App\Entity\Booking;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service for calculating platform revenue and statistics
 */
final class RevenueCalculationService
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    /**
     * Calculate total platform revenue based on completed bookings
     */
    public function calculateTotalRevenue(): float
    {
        $qb = $this->em->createQueryBuilder();
        $totalAmount = $qb->select('SUM(COALESCE(b.estimatedCost, s.price)) as total')
            ->from(Booking::class, 'b')
            ->join('b.service', 's')
            ->where('b.status = :status')
            ->setParameter('status', AppConstants::BOOKING_STATUS_COMPLETED)
            ->getQuery()
            ->getSingleScalarResult();

        $commission = (float)($totalAmount ?? 0) * AppConstants::PLATFORM_COMMISSION_PERCENTAGE;
        return round($commission, 2);
    }

    /**
     * Calculate revenue for a specific date range
     */
    public function calculateRevenueForPeriod(\DateTimeInterface $startDate, \DateTimeInterface $endDate): float
    {
        $qb = $this->em->createQueryBuilder();
        $totalAmount = $qb->select('SUM(COALESCE(b.estimatedCost, s.price)) as total')
            ->from(Booking::class, 'b')
            ->join('b.service', 's')
            ->where('b.status = :status')
            ->andWhere('b.bookingDate BETWEEN :start AND :end')
            ->setParameter('status', AppConstants::BOOKING_STATUS_COMPLETED)
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->getQuery()
            ->getSingleScalarResult();

        $commission = (float)($totalAmount ?? 0) * AppConstants::PLATFORM_COMMISSION_PERCENTAGE;
        return round($commission, 2);
    }

    /**
     * Get daily revenue trend for the last N days
     */
    public function getDailyRevenueTrend(int $days = 7): array
    {
        $data = [];
        $today = new \DateTime();

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = (clone $today)->modify("-{$i} days");
            $startOfDay = (clone $date)->setTime(0, 0, 0);
            $endOfDay = (clone $date)->setTime(23, 59, 59);

            $revenue = $this->calculateRevenueForPeriod($startOfDay, $endOfDay);
            
            $data[] = [
                'date' => $date->format('Y-m-d'),
                'label' => $date->format('D, M d'),
                'revenue' => $revenue,
            ];
        }

        return $data;
    }
}
