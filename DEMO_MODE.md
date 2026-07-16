# Demo Mode

## Overview

Demo Mode is a feature that allows you to open up the platform for testing purposes. When enabled, it removes restrictions so that:

- **Anyone can register** as a seller by default (instead of buyer)
- **Anyone can create properties** without needing a seller subscription
- **Properties can be published immediately** without payment requirements
- **No publish fees are charged** for listing properties
- **Frontend redirects are bypassed** - users won't be sent to onboarding

This is perfect for:
- Letting testers populate the platform with sample properties
- Running demo sessions
- Testing the platform before going live
- Allowing early users to try the platform without payment barriers

## How to Enable

### Step 1: Backend Configuration

Add this to your `.env` file:

```bash
DEMO_MODE=true
VITE_DEMO_MODE=true
```

> **Note**: Both variables are needed:
> - `DEMO_MODE` - for backend PHP checks
> - `VITE_DEMO_MODE` - for frontend JavaScript checks

### Step 2: Clear Configuration Cache

```bash
php artisan config:clear
```

### Step 3: Rebuild Frontend (if already built)

```bash
npm run build
```

Or if using dev mode:
```bash
npm run dev
```

### Step 4: Restart Laravel Server

```bash
php artisan serve
```

## How to Disable

Set both variables to false or remove them:

```bash
DEMO_MODE=false
VITE_DEMO_MODE=false
```

Or simply remove both lines from your `.env` file (defaults to `false`).

Then:
```bash
php artisan config:clear
npm run build
php artisan serve
```

## What Changes in Demo Mode

### Backend Changes

#### User Registration
- Users who register without specifying a role will automatically be assigned the `seller` role
- This allows them to immediately create properties

#### Property Creation
- The `StorePropertyRequest` authorization bypasses the seller/admin check
- Users can create properties regardless of their role

#### Publishing Properties
- The `PropertyService` bypasses subscription requirements
- Properties can be published immediately without payment
- No publish fees are charged
- The `SellerBillingService` skips subscription validation

### Frontend Changes

#### Router Navigation Guards
- The router no longer redirects users to the seller onboarding page
- Users can access property creation pages directly

#### Seller Billing Status
- `hasActiveSellerSubscription()` always returns `true`
- `getSellerBillingStatus()` returns an active status immediately
- No actual API calls are made for billing status

#### Listing Management
- `canManageListings()` returns `true` for all users
- Users can create, edit, and publish properties without restrictions

## Important Notes

1. **Security**: Demo mode should NEVER be enabled in production with real user data
2. **Payments**: No actual payments are processed in demo mode - everything is free
3. **Data**: All properties and users created in demo mode are real database records
4. **Cleanup**: You may want to clean up test data after disabling demo mode
5. **Both Variables Required**: You must set BOTH `DEMO_MODE` and `VITE_DEMO_MODE` for it to work fully

## Recommended Workflow

1. **Enable demo mode** in your `.env` file:
   ```bash
   DEMO_MODE=true
   VITE_DEMO_MODE=true
   ```

2. **Create a test database** or use your existing one

3. **Run migrations**: `php artisan migrate`

4. **Rebuild frontend**: `npm run build` (or `npm run dev` for development)

5. **Start the server**: `php artisan serve`

6. **Let testers register** and add properties

7. **Disable demo mode** when ready to go live:
   ```bash
   DEMO_MODE=false
   VITE_DEMO_MODE=false
   ```

8. **Rebuild and restart**:
   ```bash
   npm run build
   php artisan config:clear
   php artisan serve
   ```

## Files Modified

### Backend
- `.env.example` - Added `DEMO_MODE` and `VITE_DEMO_MODE` variables
- `config/app.php` - Added `demo_mode` configuration
- `app/Http/Requests/StorePropertyRequest.php` - Bypasses authorization in demo mode
- `app/Services/PropertyService.php` - Bypasses subscription checks in demo mode
- `app/Services/SellerBillingService.php` - Skips publish requirement enforcement in demo mode
- `app/Actions/Fortify/CreateNewUser.php` - Defaults to seller role in demo mode

### Frontend
- `resources/js/services/sellerBilling.js` - Returns active status in demo mode
- `resources/js/services/authProfile.js` - Allows listing management for all users in demo mode

## Troubleshooting

### "I still see the onboarding screen"

Make sure you have:
1. Set `DEMO_MODE=true` in your `.env`
2. Set `VITE_DEMO_MODE=true` in your `.env`
3. Run `php artisan config:clear`
4. Rebuilt your frontend with `npm run build` or `npm run dev`
5. Restarted your Laravel server

### "I can't create properties"

Check that:
1. You're logged in
2. Both demo mode variables are set to `true`
3. You've cleared the config cache
4. You've rebuilt the frontend

### "Changes aren't taking effect"

Try:
```bash
php artisan config:clear
php artisan cache:clear
npm run build
php artisan serve
```
