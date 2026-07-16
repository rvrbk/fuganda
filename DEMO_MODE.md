# Demo Mode

## Overview

Demo Mode is a feature that allows you to open up the platform for testing purposes. When enabled, it removes restrictions so that:

- **Anyone can register** as a seller by default (instead of buyer)
- **Anyone can create properties** without needing a seller subscription
- **Properties can be published immediately** without payment requirements
- **No publish fees are charged** for listing properties

This is perfect for:
- Letting testers populate the platform with sample properties
- Running demo sessions
- Testing the platform before going live
- Allowing early users to try the platform without payment barriers

## How to Enable

### Option 1: Environment Variable (Recommended)

Add this to your `.env` file:

```bash
DEMO_MODE=true
```

Then restart your Laravel server:

```bash
php artisan config:clear
php artisan serve
```

### Option 2: Temporary Testing

For quick local testing, you can enable it temporarily:

```bash
# Enable demo mode
php artisan tinker --execute="file_put_contents('.env', file_get_contents('.env').PHP_EOL.'DEMO_MODE=true');"
php artisan config:clear

# Disable demo mode
php artisan tinker --execute="file_put_contents('.env', preg_replace('/DEMO_MODE=true/', 'DEMO_MODE=false', file_get_contents('.env')));"
php artisan config:clear
```

## How to Disable

Simply set the environment variable to `false` or remove it:

```bash
DEMO_MODE=false
```

Or remove the line entirely from your `.env` file (defaults to `false`).

## What Changes in Demo Mode

### User Registration
- Users who register without specifying a role will automatically be assigned the `seller` role
- This allows them to immediately create properties

### Property Creation
- The `StorePropertyRequest` authorization bypasses the seller/admin check
- Users can create properties regardless of their role

### Publishing Properties
- The `PropertyService` bypasses subscription requirements
- Properties can be published immediately without payment
- No publish fees are charged
- The `SellerBillingService` skips subscription validation

### Important Notes

1. **Security**: Demo mode should NEVER be enabled in production with real user data
2. **Payments**: No actual payments are processed in demo mode - everything is free
3. **Data**: All properties and users created in demo mode are real database records
4. **Cleanup**: You may want to clean up test data after disabling demo mode

## Recommended Workflow

1. **Enable demo mode** in your `.env` file
2. **Create a test database** or use your existing one
3. **Run migrations**: `php artisan migrate`
4. **Start the server**: `php artisan serve`
5. **Let testers register** and add properties
6. **Disable demo mode** when ready to go live
7. **Optional**: Clean up test data with `php artisan db:seed --class=CleanupDemoData` (you'd need to create this)

## Files Modified

- `.env.example` - Added `DEMO_MODE` variable documentation
- `config/app.php` - Added `demo_mode` configuration
- `app/Http/Requests/StorePropertyRequest.php` - Bypasses authorization in demo mode
- `app/Services/PropertyService.php` - Bypasses subscription checks in demo mode
- `app/Services/SellerBillingService.php` - Skips publish requirement enforcement in demo mode
- `app/Actions/Fortify/CreateNewUser.php` - Defaults to seller role in demo mode
