# 🚀 Services Quick Setup (5 minutes)

## What You Have

Your project has **5 powerful services** already built:

| Service                | Status          | Setup Time |
| ---------------------- | --------------- | ---------- |
| 📧 Email/Notifications | ⚠️ Needs config | 2 min      |
| 💳 Stripe Payments     | ⚠️ Needs config | 3 min      |
| 💰 Revenue Tracking    | ✅ Ready        | 0 min      |
| 🎮 Gamification        | ✅ Ready        | 0 min      |
| 📝 Audit Logging       | ✅ Ready        | 0 min      |

---

## ⚡ Quick Setup (3 Steps)

### Step 1: Create .env.local File

```bash
# Copy the example file
copy .env.local.example .env.local

# Or create new file with your config
```

### Step 2: Add Your Email Configuration

```env
# In .env.local:
MAILER_DSN=gmail://your-email@gmail.com:your-app-password@default
```

> Get Gmail App Password: https://myaccount.google.com/apppasswords

### Step 3: (Optional) Add Stripe Keys

```env
# In .env.local:
STRIPE_SECRET_KEY=sk_test_YOUR_KEY_HERE
```

> Get Stripe Keys: https://dashboard.stripe.com/apikeys

---

## ✅ Verify Setup

```bash
# Check environment variables
php bin/console debug:dotenv

# Test email service
php bin/console debug:container NotificationService

# Clear cache
php bin/console cache:clear
```

---

## 🎯 What Works After Setup

### 1. Email Notifications Auto-Trigger On:

- ✅ New booking created → Email to provider
- ✅ New review submitted → Email to provider
- ✅ New user registered → Welcome email
- ✅ Booking status changes → Email to customer

### 2. Stripe Payments

- ✅ Checkout sessions created
- ✅ Redirect to Stripe hosted checkout
- ✅ Success/Cancel page handling

### 3. Revenue Tracking

- ✅ Calculate total platform revenue
- ✅ Track revenue by date range
- ✅ Generate daily trend reports

### 4. Gamification

- ✅ Auto-award points on booking completion
- ✅ Auto-assign badges for achievements
- ✅ Leaderboard calculations

### 5. Audit Logging

- ✅ Track all admin actions
- ✅ Security event logging

---

## 📝 File Locations

```
Service Implementations:
├── src/Service/
│   ├── NotificationService.php
│   ├── StripeService.php
│   ├── RevenueCalculationService.php
│   ├── GamificationService.php
│   └── AdminAuditLogger.php

Auto-Triggered Listeners:
├── src/EventListener/
│   ├── BookingNotificationListener.php
│   ├── ReviewNotificationListener.php
│   ├── UserRegistrationListener.php
│   ├── GamificationListener.php
│   ├── BookingAuditListener.php
│   ├── BillingListener.php
│   └── ExceptionListener.php

Configuration:
├── .env                    (Default values)
├── .env.local             (Your local overrides - NOT in git)
├── .env.local.example     (Template)
├── config/services.yaml   (Service definitions)
└── config/packages/
    └── mailer.yaml        (Email config)
```

---

## 🔗 Usage in Controllers

All services are auto-injected:

```php
use App\Service\NotificationService;
use App\Service\StripeService;
use App\Service\RevenueCalculationService;

class BookingController
{
    public function __construct(
        private NotificationService $notificationService,
        private StripeService $stripeService,
        private RevenueCalculationService $revenueService,
    ) {}

    public function checkout(Booking $booking)
    {
        // Create payment session
        $session = $this->stripeService->createCheckoutSession($billing);

        // Send notification
        $this->notificationService->notifyBookingUpdate(
            $provider,
            'New Booking',
            'Customer: ' . $booking->getCustomer()->getFullName()
        );

        return new RedirectResponse($session->url);
    }
}
```

---

## 🧪 Test Everything

### Test 1: Email (Create a booking)

1. Create new booking
2. Check email inbox (provider should get email)
3. Check in-app notifications in database

### Test 2: Stripe (Make a payment)

1. Go to checkout
2. Test card: `4242 4242 4242 4242`
3. Any future date, any 3-digit CVC
4. Should redirect to success page

### Test 3: Revenue (Check dashboard)

1. Go to admin dashboard
2. Should see calculated revenue
3. Check daily trend chart

---

## ⚠️ Important Notes

### Security

```bash
# ❌ NEVER commit secrets to git
.env                    # Public values (committed)
.env.local              # Secrets (NOT committed)
.env.prod.local         # Production secrets (NOT committed)
```

### Email Testing

```bash
# Test email setup without sending
php bin/console debug:dotenv

# Check mailer configuration
php bin/console debug:container mailer
```

### Stripe Testing

```bash
# Use test mode cards:
4242 4242 4242 4242  # Successful payment
4000 0000 0000 0002  # Declined card
5555 5555 5555 4444  # Mastercard test
```

---

## 🆘 Troubleshooting

### Email Not Sending?

1. Check `.env.local` has correct MAILER_DSN
2. Verify Gmail app password (not regular password)
3. Enable "Less Secure Apps" if not using app password
4. Check `var/log/dev.log` for errors

### Stripe Not Working?

1. Verify STRIPE_SECRET_KEY is in `.env.local`
2. Confirm key starts with `sk_test_` or `sk_live_`
3. Check logs in `var/log/dev.log`
4. Use Stripe test card: `4242 4242 4242 4242`

### Services Not Auto-Injecting?

1. Run `php bin/console cache:clear`
2. Verify class exists in `src/Service/`
3. Check `config/services.yaml` has configuration
4. Run `php bin/console debug:container ServiceName`

---

## 📚 Full Documentation

For detailed information:

- **SERVICE_SETUP.md** - Complete service documentation
- **DEVELOPER_GUIDE.md** - Code patterns and usage
- **IMPROVEMENTS.md** - Technical details of all changes

---

## ✨ You're Ready!

```bash
# Your setup is complete when:
✅ .env.local exists with email config
✅ Stripe keys added (optional)
✅ php bin/console cache:clear runs without errors
✅ Services show in: php bin/console debug:container
✅ Tests pass: php bin/phpunit
```

**Next**: Create a booking and watch notifications happen automatically! 🚀
