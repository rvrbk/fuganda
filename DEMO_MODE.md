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

### Single Variable Setup

Add this to your `.env` file:

```bash
DEMO_MODE=true
```

That's it! Just **one variable** controls both backend and frontend behavior.

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

Set to false or remove the line:

```bash
DEMO_MODE=false
```

Or simply remove the line from your `.env` file (defaults to `false`).

Then:
```bash
php artisan config:clear
npm run build
php artisan serve
```

## How It Works

### Backend
The backend checks `config('app.demo_mode')` which reads from the `DEMO_MODE` environment variable.

### Frontend
The frontend fetches the demo mode status from the backend via a simple API endpoint:
- `GET /api/demo-mode` returns `{"demo_mode": true/false}`
- The result is cached for the session
- Cache is reset on login, logout, and registration

This means **you only need to set `DEMO_MODE` in your `.env`** - no separate frontend variable needed!

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
5. **Single Variable**: Only `DEMO_MODE` is needed - the frontend automatically detects it from the backend

## Recommended Workflow

1. **Enable demo mode** in your `.env` file:
   ```bash
   DEMO_MODE=true
   ```

2. **Create a test database** or use your existing one

3. **Run migrations**: `php artisan migrate`

4. **Rebuild frontend**: `npm run build` (or `npm run dev` for development)

5. **Start the server**: `php artisan serve`

6. **Let testers register** and add properties

7. **Disable demo mode** when ready to go live:
   ```bash
   DEMO_MODE=false
   ```
   or remove the line entirely

8. **Rebuild and restart**:
   ```bash
   npm run build
   php artisan config:clear
   php artisan serve
   ```

## Files Modified

### Backend
- `.env.example` - Added `DEMO_MODE` variable
- `config/app.php` - Added `demo_mode` configuration
- `routes/api.php` - Added `/api/demo-mode` endpoint
- `app/Http/Requests/StorePropertyRequest.php` - Bypasses authorization in demo mode
- `app/Services/PropertyService.php` - Bypasses subscription checks in demo mode
- `app/Services/SellerBillingService.php` - Skips publish requirement enforcement in demo mode
- `app/Actions/Fortify/CreateNewUser.php` - Defaults to seller role in demo mode

### Frontend
- `resources/js/services/sellerBilling.js` - Fetches demo mode from API, returns active status when enabled
- `resources/js/services/authProfile.js` - Fetches demo mode from API, allows listing management for all users when enabled

## Troubleshooting

### "I still see the onboarding screen"

Make sure you have:
1. Set `DEMO_MODE=true` in your `.env`
2. Run `php artisan config:clear`
3. Rebuilt your frontend with `npm run build` or `npm run dev`
4. Restarted your Laravel server
5. **Hard refresh** your browser (Ctrl+Shift+R or Cmd+Shift+R)

### "I can't create properties"

Check that:
1. You're logged in
2. `DEMO_MODE=true` is set in `.env`
3. You've cleared the config cache
4. You've rebuilt the frontend
5. The `/api/demo-mode` endpoint returns `{"demo_mode": true}` (test in browser)

### "Changes aren't taking effect"

Try:
```bash
php artisan config:clear
php artisan cache:clear
npm run build
php artisan serve
```

Then hard refresh your browser.

### "I get a 404 on /api/demo-mode"

Make sure you've:
1. Set `DEMO_MODE=true` in `.env`
2. Run `php artisan config:clear`
3. Restarted your Laravel server

The endpoint should be available at `http://localhost:8000/api/demo-mode`
