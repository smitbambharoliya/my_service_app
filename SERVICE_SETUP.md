# Service Configuration Guide

## 🚀 Services Setup for Your Project

Your Service Hub has **5 core services** already implemented. Here's what needs to be configured:

---

## 1. 📧 **Email/Notification Service**

### Current Status: ✅ Partially Configured

**File**: `src/Service/NotificationService.php`

**Configuration**: Already in `.env`:

```env
MAILER_DSN=gmail://USERNAME:PASSWORD@default
MAILER_FROM=noreply@servicehub.local
```

### ✅ What's Working

- In-app notifications (stored in database)
- Email sending through NotificationService
- Event listeners auto-trigger notifications:
    - `BookingNotificationListener` - Booking updates
    - `ReviewNotificationListener` - New reviews
    - `UserRegistrationListener` - New user registration

### ⚠️ What Needs Configuration

```bash
# Update your .env.local file:
MAILER_DSN=gmail://your-email@gmail.com:your-app-password@default
MAILER_FROM=noreply@servicehub.local
```

> **Note**: For Gmail, use [App Passwords](https://myaccount.google.com/apppasswords), not your regular password

### Usage Example

```php
use App\Service\NotificationService;

class BookingController
{
    public function __construct(
        private NotificationService $notificationService,
    ) {}

    public function createBooking()
    {
        // ... booking creation ...

        // Send notification
        $this->notificationService->notifyBookingUpdate(
            $provider,
            'New Booking Request',
            'You have a new booking request from ' . $customer->getFullName(),
            $bookingUrl
        );
    }
}
```

---

## 2. 💳 **Stripe Payment Service**

### Current Status: ⚠️ Not Configured

**File**: `src/Service/StripeService.php`

**Configuration in `.env`**:

```env
STRIPE_SECRET_KEY=sk_test_... # or sk_live_... for production
```

### ❌ What's Missing

You need to add to `.env.local`:

```bash
# Get from https://dashboard.stripe.com/apikeys
STRIPE_SECRET_KEY=sk_test_YOUR_SECRET_KEY_HERE

# Optional but recommended for testing
STRIPE_PUBLISHABLE_KEY=pk_test_YOUR_PUBLISHABLE_KEY_HERE
```

### ✅ What's Implemented

- `createCheckoutSession()` - Creates Stripe payment sessions
- Handles payment redirects (success/cancel)
- Integrates with Billing entity

### Usage Example

```php
use App\Service\StripeService;

class BillingController
{
    public function __construct(
        private StripeService $stripeService,
    ) {}

    public function checkout(Billing $billing)
    {
        // Create checkout session
        $session = $this->stripeService->createCheckoutSession($billing);

        // Redirect to Stripe
        return new RedirectResponse($session->url);
    }
}
```

### Setup Steps

1. Go to [stripe.com](https://stripe.com) and create an account
2. Get your API keys from Dashboard → Developers → API Keys
3. Add to `.env.local`:
    ```
    STRIPE_SECRET_KEY=sk_test_...
    ```
4. Test with Stripe's test card: `4242 4242 4242 4242`

---

## 3. 📊 **Revenue Calculation Service**

### Current Status: ✅ **Ready to Use** (Just Created!)

**File**: `src/Service/RevenueCalculationService.php`

**Configuration**: None needed! Auto-wired in service container.

### ✅ What's Working

- Calculates total platform revenue
- Tracks revenue by date range
- Generates daily trends for charts
- Uses 15% platform commission

### Usage Example

```php
use App\Service\RevenueCalculationService;

class AdminController
{
    public function __construct(
        private RevenueCalculationService $revenueService,
    ) {}

    public function dashboard()
    {
        // Total revenue
        $totalRevenue = $this->revenueService->calculateTotalRevenue();

        // Date range
        $monthlyRevenue = $this->revenueService->calculateRevenueForPeriod(
            new DateTime('2026-04-01'),
            new DateTime('2026-04-30')
        );

        // Last 7 days trend
        $trend = $this->revenueService->getDailyRevenueTrend(7);
        // Returns: [['date' => '2026-04-24', 'revenue' => 150.50], ...]
    }
}
```

---

## 4. 🎮 **Gamification Service**

### Current Status: ✅ Configured

**File**: `src/Service/GamificationService.php`

**Configuration**: None needed!

### ✅ What's Working

- Points allocation on booking completion
- Badge system for achievements
- Leaderboard calculations
- Auto-triggered by `GamificationListener`

### Usage Example

```php
$gamificationService->awardPointsForBookingCompletion($provider, $booking);
$gamificationService->awardBadge($user, 'TOP_PROVIDER');
```

---

## 5. 📝 **Audit Logger Service**

### Current Status: ✅ Configured

**File**: `src/Service/AdminAuditLogger.php`

**Configuration**: None needed!

### ✅ What's Working

- Tracks admin actions
- Event logging for security
- Auto-triggered by `BookingAuditListener`

### Usage Example

```php
use App\Service\AdminAuditLogger;

class AdminController
{
    public function __construct(
        private AdminAuditLogger $auditLogger,
    ) {}

    public function dashboard()
    {
        // Log action
        $this->auditLogger->logAuthAction('DASHBOARD_VIEW');
    }
}
```

---

## 📋 Configuration Checklist

### Required Setup

- [ ] **Email** - Update MAILER_DSN in `.env.local`
- [ ] **Stripe** - Add STRIPE_SECRET_KEY to `.env.local`
- [ ] **Database** - Verify DATABASE_URL is correct
- [ ] **Migrations** - Run `php bin/console doctrine:migrations:migrate`

### Testing Configuration

```bash
# Test email configuration
php bin/console debug:container NotificationService

# Test Stripe setup
php bin/console debug:container StripeService

# Verify environment variables
php bin/console debug:dotenv
```

---

## 🔧 Configuration File Locations

```
.env                          # Default values (committed to git)
.env.local                    # Local overrides (NOT committed)
.env.test                     # Test environment
.env.prod                     # Production values

config/services.yaml          # Service definitions
src/Service/                  # Service implementations
src/EventListener/            # Auto-triggered listeners
```

---

## 📝 Event Listeners (Auto-Triggered)

These listeners automatically call services based on events:

| Listener                      | Trigger                   | Action                  |
| ----------------------------- | ------------------------- | ----------------------- |
| `BookingNotificationListener` | BookingCreatedEvent       | Sends email to provider |
| `ReviewNotificationListener`  | ReviewSubmittedEvent      | Sends email to provider |
| `UserRegistrationListener`    | UserRegisteredEvent       | Welcome email           |
| `GamificationListener`        | BookingCompletedEvent     | Award points/badges     |
| `BookingAuditListener`        | BookingStatusChangedEvent | Log action              |
| `BillingListener`             | PaymentCompletedEvent     | Update billing status   |

---

## 🧪 Testing Services Locally

### Test Email Service

```bash
# Send test email
php bin/console make:migration
php bin/console doctrine:migrations:execute --query "SELECT 1"
```

### Test Stripe (Use Test Mode)

1. Login to [Stripe Dashboard](https://dashboard.stripe.com)
2. Switch to **Test Mode**
3. Use test card: `4242 4242 4242 4242`
4. Any future date and any 3-digit CVC

---

## 🚀 Next Steps

### Immediate (Today)

1. Create `.env.local` file in project root
2. Add your email configuration
3. Add your Stripe keys (optional for now)

### This Week

1. Test email sending by creating a booking
2. Test payment flow with Stripe test mode
3. Verify notifications appear in database

### Before Production

1. Switch Stripe to Live Mode
2. Use production email credentials
3. Update .env.prod with secure values

---

## ⚠️ Security Notes

### Never Commit Secrets!

```bash
# ✅ GOOD - In .env.local (ignored by git)
STRIPE_SECRET_KEY=sk_live_...

# ❌ BAD - In .env (committed to repo)
STRIPE_SECRET_KEY=sk_live_...
```

### Environment-Specific

```bash
# Development (.env.local)
STRIPE_SECRET_KEY=sk_test_...

# Production (.env.prod.local - not in repo)
STRIPE_SECRET_KEY=sk_live_...
```

---

## 📚 Quick Reference

### All Services Available via Dependency Injection

```php
use App\Service\NotificationService;
use App\Service\StripeService;
use App\Service\RevenueCalculationService;
use App\Service\GamificationService;
use App\Service\AdminAuditLogger;

public function __construct(
    private NotificationService $notificationService,
    private StripeService $stripeService,
    private RevenueCalculationService $revenueService,
    private GamificationService $gamificationService,
    private AdminAuditLogger $auditLogger,
) {}
```

### Common Methods

**NotificationService**

```php
$notificationService->notifyBookingUpdate($user, $title, $message, $url);
$notificationService->notifyMessage($user, $title, $message, $url);
```

**RevenueCalculationService**

```php
$revenueService->calculateTotalRevenue();
$revenueService->calculateRevenueForPeriod($start, $end);
$revenueService->getDailyRevenueTrend(7);
```

**StripeService**

```php
$session = $stripeService->createCheckoutSession($billing);
```

---

## 🤔 Need Help?

1. Check `IMPROVEMENTS.md` for technical details
2. Check `DEVELOPER_GUIDE.md` for usage patterns
3. Review EventListener files for auto-trigger examples
4. Check `.env` for required variables
