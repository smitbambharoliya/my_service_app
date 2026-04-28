# Project Report - ServiceHub

## Overview

This project is a Symfony 7.4 application built for a service marketplace platform. It includes user registration, OTP verification, service discovery, booking, billing, notifications, chat, and admin management.

## Technology Stack

- PHP 8.2+
- Symfony 7.4
- Doctrine ORM + Doctrine Migrations
- Twig templating
- Symfony Mailer / Google Mailer
- Stripe PHP SDK
- KNP Paginator Bundle
- KNP Snappy Bundle
- Symfony UX Turbo + Stimulus
- PHPUnit 11 for tests

## Directory Structure

### `src/`

- `Controller/` - Web controllers and route handlers
- `Service/` - Business logic and reusable services
- `Entity/` - Doctrine entities for database mapping
- `Repository/` - Custom query logic and repository operations
- `Form/` - Symfony form definitions
- `Event/` - Domain events
- `EventListener/` - Event listeners and async behavior
- `Security/` - Authentication and security helpers
- `Utility/` - Helper classes and utility functions
- `Doctrine/` - Custom Doctrine DBAL types

### `templates/`

- `home/` - Landing page and public homepage UI
- `registration/` - Registration and OTP verification views
- `security/` - Login and authentication views
- `service/` - Service browsing and service detail pages
- `booking/` - Booking UI pages
- `billing/` - Billing and payment UI pages
- `admin/` - Admin dashboards and featured service manager
- `message/` - Chat interface
- `notification/` - Notification UI components
- `partials/` - Shared UI elements
- Base layout templates: `base.html.twig`, `dashboard_base.html.twig`, `dashboard_base_aurora.html.twig`

### `tests/`

- `Functional/BookingFlowTest.php` - basic functional tests for authorization and homepage access

## Core Application Features

### Authentication and User Management

- Registration flow with OTP verification
- Custom authenticator: `App\Security\UserAuthenticator`
- Role-based access control for admin, provider, and customer
- Session-based OTP verification and resend flow

### Booking and Payments

- `BookingService` handles booking creation, status updates, cancellation, and completion flows
- Booking events are dispatched such as `BookingCreatedEvent`, `BookingCompletedEvent`, and `BookingStatusChangedEvent`
- Stripe payment/billing support via `StripeService`
- `BillingService` and `RevenueCalculationService` manage premium upgrades and revenue calculations

### Notifications and Messaging

- Notification system with `NotificationService`
- AJAX-based notifications refresh using `fetch()` in `templates/partials/_notification_bell.html.twig`
- Real-time messaging/chat polling via `fetch()` in `templates/message/chat.html.twig`

### Featured / Trending Services

- `FeaturedServiceRepository::findActiveFeaturedServices()` returns active featured services for homepage sections
- Home controller uses featured service sections including trending, hero, premium, seasonal
- Admin featured service manager supports live search and featured service management

### Search and Discovery

- `ServiceRepository::smartMatchSearch()` supports query and filter-based search across services, categories, price ranges, tiers, and premium status
- `findActiveWithProvider()` returns active services with provider and category eager-loaded

### Email and OTP

- OTP email generation via `OtpService`
- Email sending through Symfony Mailer and Twig email templates
- OTP expiry and attempt tracking in user entity
- `UserRegistrationListener` sends OTP email on registration event

## Fetch / AJAX Usage

- `templates/partials/_notification_bell.html.twig` uses `fetch()` to load notifications periodically and when the bell dropdown opens
- `templates/message/chat.html.twig` uses `fetch()` for live message polling every 3 seconds and sending new messages via API POST
- `templates/admin/featured_services/manager.html.twig` uses `fetch()` for live autocomplete search when selecting services to feature

## Real-Time Updates with Turbo Streams

- Implemented Turbo Stream responses for live updates without full page reloads
- Chat system can use `templates/message/_turbo_new_message.html.twig` to append new messages in real-time
- Notification system can use `templates/notification/_turbo_new_notification.html.twig` to prepend new notifications
- Controllers can return Turbo Stream responses via `TurboStreamResponseFactory` for seamless component updates

## Review & Rating System

- `Review` entity maps reviews from customers to service providers via completed bookings
- Reviews include:
    - `rating`: 1-5 star rating (validated)
    - `comment`: Optional text feedback (max 2000 chars)
    - `customer`: User who created the review
    - `provider`: User (provider) being reviewed
    - `booking`: One-to-one relation to the booking being reviewed
- Reviews are created after booking completion and linked to the booking

## Dynamic Pricing & Vouchers

- **Voucher Entity**: Seasonal discount codes with:
    - `code`: Unique voucher code
    - `discountPercentage`: Discount % (e.g., 10% off)
    - `maxDiscountAmount`: Optional cap on discount amount
    - `startDate`, `endDate`: Validity period
    - `usageLimit`, `usageCount`: Usage tracking
    - `featuredService`: Optional link to featured service for targeted discounts
    - `isActive`: Boolean flag for activation
    - `isValid()` method to check if voucher is currently usable

- **Booking Integration**: Bookings now support:
    - `voucherCode`: Code applied to booking
    - `voucherDiscount`: Calculated discount amount

- **DiscountService**: Business logic for applying vouchers:
    - `calculateDiscountedPrice()`: Compute final price with discount
    - `applyVoucher()`: Attach voucher to booking and increment usage
    - `getActiveVouchersForService()`: Get vouchers for a featured service

- **VoucherRepository**: Query methods:
    - `findValidByCode()`: Get valid voucher by code
    - `findActiveForFeaturedService()`: Get active vouchers for a featured service

## User Interface

- Design appears modern with luxury/glass UI styling
- Separate user dashboards and admin interfaces
- Public homepage includes archive/trending section, search categories, and call-to-action buttons
- Forms for registration, login, booking, billing, OTP verification, profile updates
- Notification dropdown and chat UI provide dynamic interactions

## Empty State Templates

- **`templates/partials/_empty_state.html.twig`**: Luxury-styled component for "no results" scenarios
- Used when search yields no services or results list is empty
- Configurable title, message, action button, and action URL
- Features glass-card design matching the luxury UI theme
- Easy to include in any template: `{% include 'partials/_empty_state.html.twig' with {...} %}`

## Authorization & Security

### Voter System

- **BookingVoter**: Symfony Voter for booking-level authorization
    - `VIEW`: Only customer of booking or provider of service can view
    - `EDIT`: Only provider can edit (when booking is pending/confirmed)
    - `CANCEL`: Customer can cancel anytime; provider can cancel if not started/completed
- **Usage in Controllers**:

    ```php
    // In a controller action
    $this->denyAccessUnlessGranted('view', $booking);
    // or
    if ($this->isGranted('edit', $booking)) {
      // allow edit
    }
    ```

- **Benefits**:
    - Prevents unauthorized viewing of other users' bookings
    - Ensures providers can only modify their own service bookings
    - Customers cannot modify booking after certain statuses
    - All access checked at application level, not just template visibility

## Testing

### Existing Test Coverage

- Functional test file: `tests/Functional/BookingFlowTest.php`
- Basic tests include:
    - homepage is public and accessible
    - dashboard access requires authentication

### Recommended Test Improvements

- Add tests for registration and OTP verification flows
- Add tests for booking creation and status transitions
- Add tests for Stripe payment and webhook handling
- Add tests for featured service admin flows
- Add repository-level tests for search and trending logic

## Configuration

### Environment Management

- `.env`, `.env.local`, `.env.dev`, `.env.test` exist for environment-specific configuration
- `config/packages/framework.yaml` loads `APP_SECRET`
- `config/packages/mailer.yaml` loads `MAILER_DSN`
- `config/services.yaml` auto-wires services and configures explicit dependencies for Stripe and NotificationService

### Logging

- `config/packages/monolog.yaml`
    - dev: file logging with debug level
    - prod: `php://stderr` with JSON formatter and fingers_crossed handler

### Security

- Password hashing auto configuration
- Remember-me support enabled
- Access controls for routes by role
- Public access properly granted to login/register/OTP routes

## Production Readiness Notes

### Strong Points

- Clear separation between controllers and services
- Event-driven booking and registration workflows
- AJAX-powered notifications and chat interactions
- Good Symfony configuration structure
- New voter-based security for booking access
- Turbo Stream support for real-time updates
- Voucher system for dynamic pricing strategies

### Suggested Improvements

- Add more automated tests to cover key workflows
- Ensure production `.env.local` contains secure values for `APP_SECRET`, `DATABASE_URL`, `MAILER_DSN`, and Stripe secrets
- Add rate limiting or brute-force protection for OTP verification endpoints
- Add caching or CDN support for static assets
- Add production monitoring and error reporting
- Create database migration for new Voucher entity: `php bin/console make:migration` and `php bin/console doctrine:migrations:migrate`
- Update Booking entity migration after adding voucher fields
- Register BookingVoter in security config to enable voter-based access control

## Implementation Recommendations

### Review System

- Create a ReviewController with actions for creating and viewing reviews
- Add voting button on completed bookings: "Leave a Review"
- Display average rating and review count on service detail pages
- Consider review moderation in admin panel

### Voucher / Discount System

- Create admin UI for creating and managing vouchers
- Display active vouchers on featured service cards with visual badges
- Add discount preview in booking form before payment
- Track voucher usage in admin analytics
- Auto-apply best available voucher to cart (optional)

### Turbo Streams Implementation

- Replace `fetch()` polling in chat with `GET` requests returning Turbo Streams
- Replace notification polling with periodic Turbo Stream requests or WebSocket integration
- Add `data-turbo-stream` attributes to forms for AJAX form submissions with streaming responses
- Consider adding `TurboStreamResponseFactory` service for DRY response creation

### Empty States

- Include empty state in service search results
- Include in notification/message lists when empty
- Use in admin panels (featured services manager, voucher list, etc.)
- Customize message per context (e.g., "No services in your area" vs "No bookings yet")

### Voter Security

- Always use voters in controllers before processing user-specific resources
- Add `denyAccessUnlessGranted()` at start of sensitive actions
- Consider adding `@IsGranted` attributes to controller methods (Symfony 6+)
- Test voter logic in functional tests to ensure authorization works correctly
- Monitor access denials in production logs

## Database Migrations Required

After implementing these features, run:

```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

This will create tables for:

- `voucher` (new)
- Update `booking` table with `voucher_code` and `voucher_discount` columns

## Summary

This project is a solid Symfony application with a service marketplace architecture, OTP-based registration, featured/trending service management, booking and billing support, and interactive notification/chat UI.

To make it production-ready, add broader automated test coverage, tighten OTP security, ensure environment config is complete, and optionally implement a stronger trending algorithm for homepage display.
