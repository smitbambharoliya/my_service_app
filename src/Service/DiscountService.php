<?php

namespace App\Service;

use App\Entity\Booking;
use App\Entity\Voucher;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

class DiscountService
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    /**
     * Calculate discounted price for a booking using a voucher code
     */
    public function calculateDiscountedPrice(Booking $booking, string $voucherCode): array
    {
        $voucher = $this->em->getRepository(Voucher::class)->findValidByCode($voucherCode);

        if (!$voucher) {
            return ['status' => 'invalid', 'message' => 'Voucher code is not valid or has expired.'];
        }

        $originalPrice = $booking->getService()->getPrice();
        $discountAmount = ($originalPrice * $voucher->getDiscountPercentage()) / 100;

        if ($voucher->getMaxDiscountAmount()) {
            $maxDiscount = (float) $voucher->getMaxDiscountAmount();
            $discountAmount = min($discountAmount, $maxDiscount);
        }

        $finalPrice = $originalPrice - $discountAmount;

        return [
            'status' => 'success',
            'original_price' => $originalPrice,
            'discount_amount' => round($discountAmount, 2),
            'final_price' => round($finalPrice, 2),
            'discount_percentage' => $voucher->getDiscountPercentage(),
            'voucher_id' => $voucher->getId(),
        ];
    }

    /**
     * Apply voucher to booking and increment usage
     */
    public function applyVoucher(Booking $booking, Voucher $voucher): void
    {
        $booking->setVoucherCode($voucher->getCode());
        $booking->setVoucherDiscount((float) $this->calculateDiscountedPrice($booking, $voucher->getCode())['discount_amount']);

        $voucher->incrementUsage();

        $this->em->flush();
    }

    /**
     * Get active vouchers for a featured service
     */
    public function getActiveVouchersForService(int $serviceId): array
    {
        return $this->em->getRepository(Voucher::class)->findActiveForFeaturedService($serviceId);
    }
}
