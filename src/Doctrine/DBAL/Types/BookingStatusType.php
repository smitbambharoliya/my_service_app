<?php

namespace App\Doctrine\DBAL\Types;

use App\Constants\AppConstants;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

/**
 * Type for booking status with validation
 */
class BookingStatusType extends StringType
{
    public const NAME = 'booking_status';

    public function getName(): string
    {
        return self::NAME;
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): mixed
    {
        if (null === $value) {
            return null;
        }

        if (!\in_array($value, AppConstants::BOOKING_STATUSES, true)) {
            throw new \InvalidArgumentException(
                \sprintf('Invalid booking status: %s', $value)
            );
        }

        return $value;
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): mixed
    {
        return $value;
    }
}
