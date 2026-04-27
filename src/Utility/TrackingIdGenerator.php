<?php

namespace App\Utility;

use App\Constants\AppConstants;

/**
 * Generates unique tracking IDs for bookings and other entities
 */
final class TrackingIdGenerator
{
    /**
     * Generate a tracking ID with prefix
     * Format: TRK-ABCD1234
     */
    public static function generate(): string
    {
        $uniquePart = strtoupper(bin2hex(random_bytes(4)));
        return AppConstants::TRACKING_ID_PREFIX . $uniquePart;
    }

    /**
     * Generate a tracking ID with date prefix
     * Format: TRK-20260424-ABCD1234
     */
    public static function generateWithDate(\DateTimeInterface $date = null): string
    {
        $date = $date ?? new \DateTimeImmutable();
        $dateStr = $date->format('Ymd');
        $uniquePart = strtoupper(bin2hex(random_bytes(4)));
        return AppConstants::TRACKING_ID_PREFIX . $dateStr . '-' . $uniquePart;
    }
}
