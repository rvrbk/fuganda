<?php

use App\Models\SellerSubscription;
use App\Services\SellerBillingService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('ops:heartbeat', function () {
    Log::info('ops:heartbeat executed by scheduler sample.');

    $this->comment('Heartbeat logged.');
})->purpose('Write a scheduler heartbeat log entry');

// Scheduler logs are written through the default logger at storage/logs/laravel.log.
Schedule::command('ops:heartbeat')->everyFiveMinutes()->withoutOverlapping();

Artisan::command('billing:send-payment-reminders', function (SellerBillingService $billingService) {
    $sentCount = $billingService->sendMonthlySubscriptionPaymentRequests();

    $this->info('Billing payment reminders processed successfully.');
    $this->line('emails_sent='.$sentCount);

    return self::SUCCESS;
})->purpose('Send monthly payment request emails and overdue notifications to seller subscribers');

Schedule::command('billing:send-payment-reminders')->dailyAt('08:00')->withoutOverlapping();

Artisan::command('pesapal:register-ipn {--url=} {--type=POST}', function () {
    $baseUrl = rtrim((string) config('services.pesapal.base_url'), '/');
    $consumerKey = (string) config('services.pesapal.consumer_key');
    $consumerSecret = (string) config('services.pesapal.consumer_secret');

    $missing = [];
    if ($baseUrl === '') {
        $missing[] = 'PESAPAL_BASE_URL';
    }
    if ($consumerKey === '') {
        $missing[] = 'PESAPAL_CONSUMER_KEY';
    }
    if ($consumerSecret === '') {
        $missing[] = 'PESAPAL_CONSUMER_SECRET';
    }

    if ($missing !== []) {
        $this->error('Missing configuration: '.implode(', ', $missing));

        return self::FAILURE;
    }

    $ipnUrl = (string) ($this->option('url') ?: rtrim((string) config('app.url'), '/').'/api/webhooks/pesapal');
    $ipnType = strtoupper((string) $this->option('type'));
    if (! in_array($ipnType, ['GET', 'POST'], true)) {
        $this->error('Invalid --type value. Use GET or POST.');

        return self::FAILURE;
    }

    $tokenResponse = Http::acceptJson()->post($baseUrl.'/api/Auth/RequestToken', [
        'consumer_key' => $consumerKey,
        'consumer_secret' => $consumerSecret,
    ]);

    if ($tokenResponse->failed()) {
        $message = (string) data_get($tokenResponse->json(), 'error.message', $tokenResponse->body());
        $this->error('Failed to request Pesapal token: '.$message);

        return self::FAILURE;
    }

    $token = (string) data_get($tokenResponse->json(), 'token', data_get($tokenResponse->json(), 'access_token', ''));
    if ($token === '') {
        $this->error('Pesapal token missing in response. Body: '.$tokenResponse->body());

        return self::FAILURE;
    }

    $registerResponse = Http::withToken($token)
        ->acceptJson()
        ->post($baseUrl.'/api/URLSetup/RegisterIPN', [
            'url' => $ipnUrl,
            'ipn_notification_type' => $ipnType,
        ]);

    if ($registerResponse->failed()) {
        $message = (string) data_get($registerResponse->json(), 'error.message', $registerResponse->body());
        $this->error('Failed to register IPN: '.$message);

        return self::FAILURE;
    }

    $ipnId = (string) data_get($registerResponse->json(), 'ipn_id');
    if ($ipnId === '') {
        $this->warn('IPN registration succeeded but ipn_id was not found in response.');
        $this->line('Response: '.$registerResponse->body());

        return self::FAILURE;
    }

    $this->info('Pesapal IPN registered successfully.');
    $this->line('PESAPAL_NOTIFICATION_ID='.$ipnId);
    $this->line('IPN URL: '.$ipnUrl);
    $this->line('IPN notification type: '.$ipnType);

    return self::SUCCESS;
})->purpose('Register Pesapal IPN and output PESAPAL_NOTIFICATION_ID');

Artisan::command('pesapal:check-status {--merchant=} {--tracking=} {--latest}', function () {
    $baseUrl = rtrim((string) config('services.pesapal.base_url'), '/');
    $consumerKey = (string) config('services.pesapal.consumer_key');
    $consumerSecret = (string) config('services.pesapal.consumer_secret');

    $missing = [];
    if ($baseUrl === '') {
        $missing[] = 'PESAPAL_BASE_URL';
    }
    if ($consumerKey === '') {
        $missing[] = 'PESAPAL_CONSUMER_KEY';
    }
    if ($consumerSecret === '') {
        $missing[] = 'PESAPAL_CONSUMER_SECRET';
    }

    if ($missing !== []) {
        $this->error('Missing configuration: '.implode(', ', $missing));

        return self::FAILURE;
    }

    $merchantReference = trim((string) $this->option('merchant'));
    $orderTrackingId = trim((string) $this->option('tracking'));

    if ($this->option('latest') || ($merchantReference === '' && $orderTrackingId === '')) {
        $latestSubscription = SellerSubscription::query()->latest('id')->first();
        if ($latestSubscription) {
            if ($merchantReference === '') {
                $merchantReference = (string) ($latestSubscription->provider_reference ?? '');
            }
            if ($orderTrackingId === '') {
                $orderTrackingId = (string) ($latestSubscription->provider_transaction_id ?? $latestSubscription->checkout_session_id ?? '');
            }

            $this->line('Using latest subscription #'.$latestSubscription->id);
        }
    }

    if ($merchantReference === '' && $orderTrackingId === '') {
        $this->error('Provide --merchant and/or --tracking, or use --latest.');

        return self::FAILURE;
    }

    $tokenResponse = Http::acceptJson()->post($baseUrl.'/api/Auth/RequestToken', [
        'consumer_key' => $consumerKey,
        'consumer_secret' => $consumerSecret,
    ]);

    if ($tokenResponse->failed()) {
        $message = (string) data_get($tokenResponse->json(), 'error.message', $tokenResponse->body());
        $this->error('Failed to request Pesapal token: '.$message);

        return self::FAILURE;
    }

    $token = (string) data_get($tokenResponse->json(), 'token', data_get($tokenResponse->json(), 'access_token', ''));
    if ($token === '') {
        $this->error('Pesapal token missing in response. Body: '.$tokenResponse->body());

        return self::FAILURE;
    }

    $query = [];
    if ($merchantReference !== '') {
        $query['merchantReference'] = $merchantReference;
    }
    if ($orderTrackingId !== '') {
        $query['orderTrackingId'] = $orderTrackingId;
    }

    $statusResponse = Http::withToken($token)
        ->acceptJson()
        ->get($baseUrl.'/api/Transactions/GetTransactionStatus', $query);

    if ($statusResponse->failed()) {
        $message = (string) data_get($statusResponse->json(), 'error.message', $statusResponse->body());
        $this->error('Status request failed: '.$message);

        return self::FAILURE;
    }

    $payload = $statusResponse->json();
    $statusText = strtolower((string) data_get($payload, 'payment_status_description', data_get($payload, 'payment_status', '')));
    $isPaid = str_contains($statusText, 'success')
        || str_contains($statusText, 'succeed')
        || str_contains($statusText, 'complete')
        || str_contains($statusText, 'paid');

    $this->info('Pesapal status fetched successfully.');
    $this->line('merchant_reference: '.($merchantReference !== '' ? $merchantReference : '(n/a)'));
    $this->line('order_tracking_id: '.($orderTrackingId !== '' ? $orderTrackingId : '(n/a)'));
    $this->line('payment_status_description: '.((string) data_get($payload, 'payment_status_description', '(n/a)')));
    $this->line('payment_status: '.((string) data_get($payload, 'payment_status', '(n/a)')));
    $this->line('would_activate_in_app: '.($isPaid ? 'yes' : 'no'));
    $this->line('raw_payload: '.json_encode($payload, JSON_UNESCAPED_SLASHES));

    return self::SUCCESS;
})->purpose('Check raw Pesapal transaction status for latest or specified references');

Artisan::command('billing:mark-paid {--subscription=} {--user=} {--reference=} {--force}', function () {
    $isSafeEnvironment = app()->environment(['local', 'testing']);
    if (! $isSafeEnvironment && ! $this->option('force')) {
        $this->error('This command is restricted to local/testing. Use --force if you really intend to run it elsewhere.');

        return self::FAILURE;
    }

    $subscriptionId = (int) $this->option('subscription');
    $userId = (int) $this->option('user');
    $reference = trim((string) $this->option('reference'));

    $query = SellerSubscription::query();
    if ($subscriptionId > 0) {
        $query->where('id', $subscriptionId);
    } elseif ($userId > 0) {
        $query->where('user_id', $userId)->latest('id');
    } elseif ($reference !== '') {
        $query->where('provider_reference', $reference)
            ->orWhere('provider_transaction_id', $reference)
            ->orWhere('checkout_session_id', $reference);
    } else {
        $query->latest('id');
    }

    $subscription = $query->first();
    if (! $subscription) {
        $this->error('No matching subscription found.');

        return self::FAILURE;
    }

    $subscription->fill([
        'provider' => 'pesapal',
        'status' => 'active',
        'payment_status' => 'paid',
        'activated_at' => now(),
        'started_at' => now(),
        'renews_at' => now()->addMonth(),
        'canceled_at' => null,
        'callback_received_at' => now(),
        'provider_last_event_id' => 'manual_test_'.now()->timestamp,
    ]);
    $subscription->save();

    $this->info('Subscription marked as paid/active for testing.');
    $this->line('subscription_id='.$subscription->id);
    $this->line('user_id='.$subscription->user_id);
    $this->line('status='.$subscription->status);
    $this->line('payment_status='.$subscription->payment_status);

    return self::SUCCESS;
})->purpose('Local/testing helper: mark a subscription as paid to test full flow');

Artisan::command('billing:show-latest', function () {
    $subscription = SellerSubscription::query()->latest('id')->first();
    if (! $subscription) {
        $this->warn('No subscriptions found.');

        return self::SUCCESS;
    }

    $this->line('id='.$subscription->id);
    $this->line('user_id='.$subscription->user_id);
    $this->line('status='.$subscription->status);
    $this->line('payment_status='.$subscription->payment_status);
    $this->line('provider_reference='.(string) ($subscription->provider_reference ?? ''));
    $this->line('provider_transaction_id='.(string) ($subscription->provider_transaction_id ?? ''));
    $this->line('checkout_session_id='.(string) ($subscription->checkout_session_id ?? ''));

    return self::SUCCESS;
})->purpose('Show latest seller subscription status');

Artisan::command('billing:mark-pending {--subscription=} {--user=}', function () {
    $subscriptionId = (int) $this->option('subscription');
    $userId = (int) $this->option('user');

    $query = SellerSubscription::query();
    if ($subscriptionId > 0) {
        $query->where('id', $subscriptionId);
    } elseif ($userId > 0) {
        $query->where('user_id', $userId)->latest('id');
    } else {
        $query->latest('id');
    }

    $subscription = $query->first();
    if (! $subscription) {
        $this->error('No matching subscription found.');

        return self::FAILURE;
    }

    $subscription->fill([
        'status' => 'inactive',
        'payment_status' => 'pending',
        'activated_at' => null,
        'canceled_at' => null,
    ]);
    $subscription->save();

    $this->info('Subscription reset to pending/inactive.');
    $this->line('subscription_id='.$subscription->id);
    $this->line('user_id='.$subscription->user_id);
    $this->line('status='.$subscription->status);
    $this->line('payment_status='.$subscription->payment_status);

    return self::SUCCESS;
})->purpose('Local/testing helper: reset subscription to pending/inactive');
