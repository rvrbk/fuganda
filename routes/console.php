<?php

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
        $this->error('Please provide --merchant and/or --tracking. The --latest option is no longer available.');

        return self::FAILURE;
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
