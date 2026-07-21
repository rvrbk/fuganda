# Verbeek.ug Real Estates

Laravel 13 API-first multi-tenant SPA scaffold for Uganda-focused real estate platform.

## Stack

- Laravel 13 (root structure)
- Fortify + Sanctum (SPA session auth)
- Spatie Multitenancy + Translatable
- Vue 3 + Vue Router + Vue i18n
- Tailwind CSS v4 + Element Plus
- Leaflet
- Vite + PWA plugin
- Playwright e2e baseline

## Local setup

1. Install PHP and Node dependencies.

```bash
composer run setup
```

2. If needed, rerun database setup manually.

```bash
php artisan migrate
```

3. Start development processes.

```bash
composer run dev
```

4. Run backend and e2e tests.

```bash
composer run test
```

## Tenant setup checklist

1. Create a corporation tenant record in `corporations`.
2. Set an optional unique domain on the tenant (nullable unique).
3. Link users via `users.corporation_id`.
4. Authenticate via Sanctum/Fortify session flow.
5. Access tenant routes under `/api/tenant/*`.

## API route groups

- Public: `/api/public/*`
- Authenticated: `/api/auth/*` with `auth:sanctum`
- Tenant authenticated: `/api/tenant/*` with `auth:sanctum` + `tenant`

## Scheduler

Sample command and scheduler registration live in `routes/console.php`.

Run scheduler worker locally:

```bash
php artisan schedule:work
```

Production cron entry:

```bash
* * * * * cd /path/to/verbeek-ug-real-estates && php artisan schedule:run >> storage/logs/scheduler-cron.log 2>&1
```

Heartbeat sample writes to `storage/logs/laravel.log`.

## Resend mail config

The app resolves Resend key from:

1. `RESEND_API_KEY`
2. `RESEND_KEY` (fallback)
