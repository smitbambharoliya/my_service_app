<?php

namespace App\Constants;

/**
 * Application-wide constants for status values, roles, and configurations
 */
final class AppConstants
{
    // Booking statuses
    public const BOOKING_STATUS_PENDING = 'pending';
    public const BOOKING_STATUS_CONFIRMED = 'confirmed';
    public const BOOKING_STATUS_IN_PROGRESS = 'in_progress';
    public const BOOKING_STATUS_COMPLETED = 'completed';
    public const BOOKING_STATUS_CANCELLED = 'cancelled';
    
    public const BOOKING_STATUSES = [
        self::BOOKING_STATUS_PENDING,
        self::BOOKING_STATUS_CONFIRMED,
        self::BOOKING_STATUS_IN_PROGRESS,
        self::BOOKING_STATUS_COMPLETED,
        self::BOOKING_STATUS_CANCELLED,
    ];

    // Booking types
    public const BOOKING_TYPE_ONLINE = 'online';
    public const BOOKING_TYPE_ONSITE = 'onsite';
    
    public const BOOKING_TYPES = [
        self::BOOKING_TYPE_ONLINE,
        self::BOOKING_TYPE_ONSITE,
    ];

    // User roles
    public const ROLE_USER = 'ROLE_USER';
    public const ROLE_PROVIDER = 'ROLE_PROVIDER';
    public const ROLE_ADMIN = 'ROLE_ADMIN';

    // Service tiers
    public const SERVICE_TIER_BASIC = 'basic';
    public const SERVICE_TIER_STANDARD = 'standard';
    public const SERVICE_TIER_PREMIUM = 'premium';

    // Business constants
    public const PLATFORM_COMMISSION_PERCENTAGE = 0.15; // 15%
    public const TRACKING_ID_PREFIX = 'TRK-';
    
    // Pagination
    public const ITEMS_PER_PAGE = 12;
    public const ADMIN_ITEMS_PER_PAGE = 20;
    
    // Notification types
    public const NOTIFICATION_BOOKING_CREATED = 'booking_created';
    public const NOTIFICATION_BOOKING_ACCEPTED = 'booking_accepted';
    public const NOTIFICATION_BOOKING_COMPLETED = 'booking_completed';
    public const NOTIFICATION_PAYMENT_RECEIVED = 'payment_received';
    public const NOTIFICATION_REVIEW_RECEIVED = 'review_received';
    
    // Rating constraints
    public const MIN_RATING = 1;
    public const MAX_RATING = 5;
    
    // Service listing
    public const FEATURED_SERVICES_LIMIT = 6;
    public const TOP_PROVIDERS_LIMIT = 5;
    public const RECENT_ITEMS_LIMIT = 5;

    private function __construct()
    {
        // Private constructor to prevent instantiation
    }
}
