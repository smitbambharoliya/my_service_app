# 🚀 Deployment & Testing Guide

All critical security and quality issues have been fixed in the ServiceHub project. Follow this guide to deploy and test the changes.

## Pre-Deployment Checklist

- [x] All 11 critical/high-priority issues fixed
- [x] PHP syntax verified in key files
- [x] Database migration created
- [x] Environment variables added

## Step 1: Update Environment Variables

Edit `.env` and verify these new variables are present:

```bash
# Email configuration
MAILER_FROM=noreply@servicehub.local

# Billing configuration
PREMIUM_PLAN_PRICE=999.00

# OTP security configuration
OTP_EXPIRY_MINUTES=10
OTP_MAX_ATTEMPTS=5
```

## Step 2: Run Database Migration

Execute the migration to add OTP security fields:

```bash
php bin/console doctrine:migrations:migrate
```

Expected output:

```
 >> migrating Version20260406110000
    ->> INSERT INTO doctrine_migration_versions ...

 [ok] successfully migrated to version: 20260406110000
```

## Step 3: Clear Symfony Cache

```bash
php bin/console cache:clear --env=dev
php bin/console cache:clear --env=prod
```

## Step 4: Run Tests

### Test OTP Rate Limiting

1. Navigate to registration page
2. Register a new account
3. Try entering wrong OTP 5+ times
4. Verify error message shows "Maximum OTP verification attempts exceeded"
5. Request new OTP
6. Verify attempts counter resets

### Test OTP Expiration

1. Register a new account
2. Wait 11 minutes (configured as 10 minute expiry)
3. Try to verify with correct OTP
4. Verify error message shows "Your OTP has expired"

### Test CSRF Protection

1. Try to submit forms without CSRF tokens
2. Should receive "Invalid CSRF token" error
3. Forms with CSRF tokens should submit successfully

### Test Admin Authorization

1. Login as non-admin user
2. Try to access `/admin/dashboard`
3. Should be redirected to login/unauthorized page
4. Login as admin
5. Should have access to admin routes

### Test Input Validation

1. Try to create service with:
    - Empty title: Should show "Service title is required"
    - Title < 3 characters: Should show length error
    - Negative price: Should show "Price must be greater than 0"
    - Empty category: Should show "Category is required"

## Step 5: Monitor Logs

Watch for any errors after deployment:

```bash
tail -f var/log/dev.log
```

## Performance Verification

Check that admin dashboard now uses optimized queries:

1. Login as admin
2. Go to `/admin/dashboard`
3. Check page load time (should be faster than before)
4. Monitor database query count (should be reduced)

## Rollback Plan

If issues occur:

```bash
# Rollback database migration
php bin/console doctrine:migrations:migrate --previous

# Restore .env to previous state
git checkout .env

# Clear cache
php bin/console cache:clear
```

## Testing Checklist

| Test                         | Expected Result                      | Status |
| ---------------------------- | ------------------------------------ | ------ |
| OTP expires after 10 minutes | User cannot verify expired OTP       | [ ]    |
| OTP blocks after 5 attempts  | User gets error and must resend      | [ ]    |
| CSRF tokens required         | Forms reject missing CSRF token      | [ ]    |
| Admin routes protected       | Non-admin cannot access `/admin`     | [ ]    |
| Form validation active       | Invalid data rejected with messages  | [ ]    |
| Admin dashboard optimized    | Page loads faster with fewer queries | [ ]    |
| Transaction IDs improved     | 64-bit entropy visible in trans. ID  | [ ]    |
| Email config from ENV        | App uses `MAILER_FROM` not hardcoded | [ ]    |

## Monitoring Post-Deployment

### Key Metrics to Monitor

1. **OTP Verification Success Rate**: Should remain ~95%+
2. **Failed Login Attempts**: Should see spike in OTP brute force attempts blocked
3. **Admin Dashboard Query Time**: Should decrease by ~30-50%
4. **CSRF Token Validation**: Monitor for legitimate users hitting CSRF errors

### Alert Thresholds

- If >10% OTP verification failures: Check email delivery
- If admin dashboard queries spike: Check database connection
- If CSRF failures spike: Check JavaScript is loading properly

## Documentation Files

- `FIXES_IMPLEMENTED.md` - Complete list of all fixes
- `.env` - New environment variables
- `migrations/Version20260406110000.php` - Database changes
- Modified controller files - CSRF and validation improvements

---

Questions? Check the detailed explanation in `FIXES_IMPLEMENTED.md`
