# Service Hub - Developer Quick Reference

## 🚀 Key Improvements Overview

This document provides a quick reference for developers working on the Service Hub project after recent improvements.

---

## 📋 Constants - Always Use These!

Instead of hardcoding values, use `App\Constants\AppConstants`:

```php
// ✅ GOOD
use App\Constants\AppConstants;

$booking->setStatus(AppConstants::BOOKING_STATUS_PENDING);
$booking->setBookingType(AppConstants::BOOKING_TYPE_ONLINE);

if ($this->isGranted(AppConstants::ROLE_ADMIN)) {
    // ...
}

// ❌ AVOID
$booking->setStatus('pending');
$booking->setBookingType('online');
```

### Common Constants

```php
// Booking
AppConstants::BOOKING_STATUS_PENDING
AppConstants::BOOKING_STATUS_CONFIRMED
AppConstants::BOOKING_STATUS_COMPLETED
AppConstants::BOOKING_TYPE_ONLINE
AppConstants::BOOKING_TYPE_ONSITE

// User Roles
AppConstants::ROLE_USER
AppConstants::ROLE_PROVIDER
AppConstants::ROLE_ADMIN

// Business
AppConstants::FEATURED_SERVICES_LIMIT = 6
AppConstants::TOP_PROVIDERS_LIMIT = 5
AppConstants::PLATFORM_COMMISSION_PERCENTAGE = 0.15
```

---

## 🎟️ Tracking IDs

Always use the generator utility:

```php
use App\Utility\TrackingIdGenerator;

// Basic format: TRK-ABCD1234
$trackingId = TrackingIdGenerator::generate();

// Date format: TRK-20260424-ABCD1234
$trackingId = TrackingIdGenerator::generateWithDate();
```

---

## ⚡ Query Optimization Tips

### DON'T Create N+1 Queries

❌ **Bad**: Iterating over collections causes extra queries

```php
foreach ($users as $user) {
    echo $user->getServices()->count(); // Query for each user!
}
```

✅ **Good**: Use optimized repository methods

```php
// Single query, all data loaded
$services = $serviceRepo->findActiveWithProvider();

// Get provider stats efficiently
$stats = $userRepo->getProviderStats($provider);
// Returns: completed_jobs, average_rating, review_count
```

### Service Repository Methods

```php
use App\Repository\ServiceRepository;

// Get active services with provider/category eager loaded
$services = $repo->findActiveWithProvider(limit: 12);

// Search with optimization
$results = $repo->smartMatchSearch(
    query: 'plumbing',
    category: 'Home Services',
    priceRange: '₹1,000 - ₹5,000',
    tier: 'premium',
    isPremium: true
);
```

### User Repository Methods

```php
use App\Repository\UserRepository;

// Get provider stats (no N+1 queries!)
$stats = $repo->getProviderStats($user);
// Returns: ['completed_jobs' => 42, 'average_rating' => 4.5, 'review_count' => 10]

// Get other services (excludes current)
$otherServices = $repo->getProviderActiveServices($user, excludeService: $currentService);
```

---

## 💰 Revenue Calculations

Don't calculate revenue manually. Use the service:

```php
use App\Service\RevenueCalculationService;

class AdminController
{
    public function __construct(
        private RevenueCalculationService $revenueService,
    ) {}

    public function dashboard()
    {
        // Get total platform revenue
        $totalRevenue = $this->revenueService->calculateTotalRevenue();

        // Get revenue for date range
        $startDate = new DateTime('2026-04-01');
        $endDate = new DateTime('2026-04-30');
        $monthlyRevenue = $this->revenueService->calculateRevenueForPeriod($startDate, $endDate);

        // Get daily trend (last 7 days)
        $trend = $this->revenueService->getDailyRevenueTrend(days: 7);
        // Returns: [['date' => '2026-04-24', 'label' => 'Thu, Apr 24', 'revenue' => 150.50], ...]
    }
}
```

---

## 🗄️ Database Migrations

New indexes have been added for performance:

```bash
# Apply migrations
php bin/console doctrine:migrations:migrate

# See migration list
php bin/console doctrine:migrations:list
```

### Indexed Columns

- Service: provider_id, category_id, is_active, is_premium
- Booking: customer_id, service_id, status, booking_date
- Review: customer_id, provider_id
- User: email, is_verified
- Message: sender_id, recipient_id

---

## 🛡️ Exception Handling

Global exception listener is active. No need to manually handle all exceptions.

```php
// Exceptions are automatically:
// 1. Logged with context
// 2. Rendered as user-friendly pages
// 3. Never show sensitive info

throw new \RuntimeException('Something went wrong');
// → Logged to error channel
// → User sees: "An error occurred while processing your request"
```

---

## 📝 Dependency Injection Patterns

Use constructor injection for services:

```php
use App\Service\RevenueCalculationService;
use App\Repository\UserRepository;

class AdminController
{
    public function __construct(
        private RevenueCalculationService $revenueService,
        private UserRepository $userRepo,
    ) {}

    public function dashboard()
    {
        $revenue = $this->revenueService->calculateTotalRevenue();
        $stats = $this->userRepo->getProviderStats($user);
    }
}
```

---

## 🔍 Common Queries

### Service Listing

```php
// Get active services (optimized)
$services = $serviceRepo->findActiveWithProvider(limit: 12);
```

### User Dashboard

```php
// Get provider stats
$stats = $userRepo->getProviderStats($currentUser);
// Uses: completed_jobs, average_rating, review_count

// Get their other services
$services = $userRepo->getProviderActiveServices($currentUser);
```

### Admin Dashboard

```php
// Revenue
$revenue = $revenueService->calculateTotalRevenue();

// Daily trend
$trend = $revenueService->getDailyRevenueTrend(7);

// Users, bookings (use findBy with constants)
$recentUsers = $em->getRepository(User::class)
    ->findBy([], ['createdAt' => 'DESC'], AppConstants::RECENT_ITEMS_LIMIT);
```

---

## 🚨 Performance Checklist

Before committing code, check:

- [ ] Not using `.toArray()` or looping over relationships?
- [ ] Using eager loading (`->addSelect()`) for joins?
- [ ] Using repository methods instead of inline queries?
- [ ] Using `AppConstants` instead of magic strings?
- [ ] Not querying for each item in a loop?
- [ ] Using indexed columns in WHERE/ORDER BY?

---

## 📚 File Locations

```
src/
├── Constants/AppConstants.php          # All constants
├── Doctrine/DBAL/Types/               # Custom field types
├── EventListener/ExceptionListener.php # Error handling
├── Service/
│   ├── RevenueCalculationService.php  # Revenue logic
│   └── ...
├── Utility/TrackingIdGenerator.php    # ID generation
├── Repository/                         # Data access
│   ├── ServiceRepository.php
│   └── UserRepository.php
└── Controller/                         # Request handlers
    ├── HomeController.php
    ├── ServiceController.php
    └── AdminController.php

migrations/
└── Version20260424120000.php           # Database indexes
```

---

## 🧪 Testing Tips

```bash
# Run tests
php bin/phpunit

# Test specific class
php bin/phpunit tests/Controller/ServiceControllerTest.php

# Clear cache before testing
php bin/console cache:clear --env=test
```

---

## 📖 Additional Documentation

See [IMPROVEMENTS.md](IMPROVEMENTS.md) for detailed technical documentation of all changes.

---

## ⚠️ Common Mistakes to Avoid

1. ❌ Using hardcoded strings → ✅ Use `AppConstants`
2. ❌ Generating tracking IDs manually → ✅ Use `TrackingIdGenerator`
3. ❌ Calculating revenue from counts → ✅ Use `RevenueCalculationService`
4. ❌ Looping and querying → ✅ Use repository methods with eager loading
5. ❌ Ignoring exceptions → ✅ Let listener handle them
6. ❌ Using `findBy()` without optimization → ✅ Use specific repository methods

---

**Need help? Check the IMPROVEMENTS.md file or review the examples above.**
