<?php

namespace App\Services;

use App\Mail\SellerBillingPaymentMail;
use App\Models\BuyerSubscription;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class BuyerBillingService
{
    private const PROVIDER_PESAPAL = 'pesapal';

    private const DEFAULT_PLAN_CODE = 'buyer_monthly';

    private const DEFAULT_SUBSCRIPTION_AMOUNT_UGX = 10000;
    private const DEFAULT_SUBSCRIPTION_AMOUNT_USD = 3;

    private const PESAPAL_AUTH_PATH = '/api/Auth/RequestToken';
    private const PESAPAL_SUBMIT_ORDER_PATH = '/api/Transactions/SubmitOrderRequest';
    private const PESAPAL_STATUS_PATH = '/api/Transactions/GetTransactionStatus';

    public function statusFor(User $user): array
    {
        $subscription = $user->buyerSubscription;
        if ($subscription) {
            $subscription = $this->syncPendingPesapalSubscription($subscription);
            $subscription = $this->markSubscriptionPastDueAfterGrace($subscription);
        }

        $hasActiveSubscription = $subscription?->isActive() ?? false;

        $subscriptionAmountUgx = $this->effectiveSubscriptionAmountUgx();

        return [
            'buyer_has_active_subscription' => $hasActiveSubscription,
            'buyer_subscription_status' => $subscription?->status ?? 'inactive',
            'account_email' => (string) ($user->email ?? ''),
            'subscription' => $subscription,
            'pricing' => [
                'subscription' => [
                    'UGX' => $subscriptionAmountUgx,
                    'USD' => self::DEFAULT_SUBSCRIPTION_AMOUNT_USD,
                ],
            ],
        ];
    }

    public function effectiveSubscriptionAmountUgx(): int
    {
        return self::DEFAULT_SUBSCRIPTION_AMOUNT_UGX;
    }

    public function createCheckoutSession(User $user, array $attributes = []): array
    {
        if (app()->environment('testing')) {
            $subscription = $this->chargeSubscription($user);

            return [
                'subscription' => $subscription,
                'checkout' => null,
            ];
        }

        $baseUrl = rtrim((string) config('services.pesapal.base_url'), '/');
        $consumerKey = (string) config('services.pesapal.consumer_key');
        $consumerSecret = (string) config('services.pesapal.consumer_secret');
        $notificationId = (string) config('services.pesapal.notification_id');

        $missingConfig = [];
        if ($baseUrl === '') {
            $missingConfig[] = 'PESAPAL_BASE_URL';
        }
        if ($consumerKey === '') {
            $missingConfig[] = 'PESAPAL_CONSUMER_KEY';
        }
        if ($consumerSecret === '') {
            $missingConfig[] = 'PESAPAL_CONSUMER_SECRET';
        }
        if ($notificationId === '') {
            $missingConfig[] = 'PESAPAL_NOTIFICATION_ID';
        }

        if ($missingConfig !== []) {
            throw ValidationException::withMessages([
                'status' => ['Payment service is not configured. Missing: '.implode(', ', $missingConfig).'.'],
            ]);
        }

        $paymentMethod = (string) ($attributes['payment_method'] ?? 'mobile_money');
        if (! in_array($paymentMethod, ['mobile_money', 'card'], true)) {
            $paymentMethod = 'mobile_money';
        }

        $amount = $this->effectiveSubscriptionAmountUgx();
        $providerReference = sprintf('buyer_sub_%d_%s', $user->id, now()->format('YmdHis'));

        $subscription = BuyerSubscription::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'plan_code' => self::DEFAULT_PLAN_CODE,
                'amount_ugx' => $amount,
                'currency' => 'UGX',
                'provider' => self::PROVIDER_PESAPAL,
                'status' => 'inactive',
                'payment_status' => 'pending',
                'reference' => $providerReference,
                'provider_transaction_id' => null,
                'provider_last_event_id' => null,
                'callback_received_at' => null,
                'checkout_session_id' => null,
                'billing_email' => $attributes['billing_email'] ?? $user->email,
                'payment_method' => $paymentMethod,
                'payment_request_sent_at' => now(),
            ]
        );

        $token = $this->requestPesapalToken($baseUrl, $consumerKey, $consumerSecret);
        $callbackUrl = (string) config('services.pesapal.callback_url');
        if ($callbackUrl === '') {
            $callbackUrl = rtrim((string) config('app.url'), '/').'/api/callbacks/pesapal';
        }

        $payload = [
            'id' => $providerReference,
            'currency' => 'UGX',
            'amount' => $amount,
            'description' => sprintf('Buyer subscription for %s', $user->name ?? 'User'),
            'callback_url' => $callbackUrl,
            'notification_id' => $notificationId,
            'branch' => 'verbeek-ug-real-estates',
            'channel' => $paymentMethod === 'mobile_money' ? 'MOBILE' : 'CARD',
            'billing_address' => [
                'email_address' => (string) ($user->email ?? ''),
                'phone_number' => (string) ($attributes['phone_number'] ?? ''),
                'country_code' => 'UG',
                'first_name' => (string) ($user->name ?? 'Buyer'),
            ],
            'metadata' => [
                'user_id' => (string) $user->id,
                'subscription_id' => (string) $subscription->id,
                'provider' => self::PROVIDER_PESAPAL,
                'type' => 'buyer_subscription',
            ],
        ];

        $response = Http::withToken($token)
            ->acceptJson()
            ->post($baseUrl.self::PESAPAL_SUBMIT_ORDER_PATH, $payload);

        if ($response->failed()) {
            $errorMessage = (string) data_get($response->json(), 'error.message', 'Unable to create buyer subscription checkout session.');

            throw ValidationException::withMessages([
                'status' => [$errorMessage],
            ]);
        }

        $order = $response->json();
        $orderTrackingId = (string) data_get($order, 'order_tracking_id', data_get($order, 'OrderTrackingId', data_get($order, 'data.order_tracking_id', '')));
        $merchantReference = (string) data_get($order, 'merchant_reference', data_get($order, 'MerchantReference', data_get($order, 'data.merchant_reference', $providerReference)));
        $redirectUrl = (string) data_get(
            $order,
            'redirect_url',
            data_get(
                $order,
                'RedirectUrl',
                data_get(
                    $order,
                    'data.redirect_url',
                    ''
                )
            )
        );

        if ($redirectUrl === '') {
            throw ValidationException::withMessages([
                'status' => ['No redirect URL received from payment provider.'],
            ]);
        }

        $subscription->update([
            'provider_transaction_id' => $orderTrackingId,
            'checkout_session_id' => $merchantReference,
            'status' => 'pending',
        ]);

        return [
            'subscription' => $subscription->fresh(),
            'checkout' => [
                'url' => $redirectUrl,
                'payment_status' => 'pending',
                'order_tracking_id' => $orderTrackingId,
                'merchant_reference' => $merchantReference,
            ],
        ];
    }

    public function hasActiveSubscription(User $user): bool
    {
        $subscription = $user->buyerSubscription;
        if ($subscription) {
            $subscription = $this->markSubscriptionPastDueAfterGrace($subscription);
        }

        return $subscription?->isActive() ?? false;
    }

    public function cancel(User $user): BuyerSubscription
    {
        $subscription = $user->buyerSubscription;
        if ($subscription) {
            $subscription->update([
                'status' => 'inactive',
                'canceled_at' => now(),
            ]);
        }

        return $subscription ?? new BuyerSubscription();
    }

    public function syncPendingPesapalSubscription(BuyerSubscription $subscription): BuyerSubscription
    {
        if ($subscription->status !== 'pending' || $subscription->provider !== self::PROVIDER_PESAPAL) {
            return $subscription;
        }

        $baseUrl = rtrim((string) config('services.pesapal.base_url'), '/');
        $consumerKey = (string) config('services.pesapal.consumer_key');
        $consumerSecret = (string) config('services.pesapal.consumer_secret');

        if ($baseUrl === '' || $consumerKey === '' || $consumerSecret === '') {
            return $subscription;
        }

        $orderTrackingId = $subscription->provider_transaction_id;
        if ($orderTrackingId === null) {
            return $subscription;
        }

        try {
            $token = $this->requestPesapalToken($baseUrl, $consumerKey, $consumerSecret);
            $response = Http::withToken($token)
                ->acceptJson()
                ->get($baseUrl.self::PESAPAL_STATUS_PATH, [
                    'order_tracking_id' => $orderTrackingId,
                ]);

            if ($response->successful()) {
                $statusData = $response->json();
                $status = strtolower((string) data_get($statusData, 'status', data_get($statusData, 'Status', data_get($statusData, 'data.status', ''))));

                if (in_array($status, ['completed', 'successful', 'success', 'paid'], true)) {
                    $subscription->update([
                        'status' => 'active',
                        'payment_status' => 'paid',
                        'activated_at' => now(),
                        'started_at' => now(),
                        'renews_at' => now()->addMonth(),
                        'callback_received_at' => now(),
                    ]);

                    try {
                        if (! empty($subscription->billing_email)) {
                            Mail::to($subscription->billing_email)->send(new BuyerBillingPaymentMail($subscription));
                        }
                    } catch (\Throwable $e) {
                        report($e);
                    }
                } elseif (in_array($status, ['failed', 'declined', 'invalid', 'expired'], true)) {
                    $subscription->update([
                        'status' => 'inactive',
                        'payment_status' => 'failed',
                        'callback_received_at' => now(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            report($e);
        }

        return $subscription;
    }

    public function markSubscriptionPastDueAfterGrace(BuyerSubscription $subscription): BuyerSubscription
    {
        if ($subscription->status !== 'active') {
            return $subscription;
        }

        $renewsAt = $subscription->renews_at;
        if ($renewsAt === null) {
            return $subscription;
        }

        $gracePeriodEndsAt = $renewsAt->copy()->addDays(7);

        if (now()->isAfter($gracePeriodEndsAt)) {
            $subscription->update([
                'status' => 'inactive',
                'canceled_at' => now(),
            ]);
        } elseif (now()->isAfter($renewsAt)) {
            $subscription->update([
                'status' => 'past_due',
            ]);
        }

        return $subscription;
    }

    public function handlePesapalWebhookPayload(string $payload): void
    {
        $data = json_decode($payload, true);
        if (! is_array($data)) {
            return;
        }

        $merchantReference = strtolower((string) data_get($data, 'MerchantReference', data_get($data, 'merchant_reference', '')));
        $status = strtolower((string) data_get($data, 'Status', data_get($data, 'status', '')));

        if (! str_starts_with($merchantReference, 'buyer_sub_')) {
            return;
        }

        $subscription = BuyerSubscription::query()
            ->where('reference', $merchantReference)
            ->orWhere('provider_transaction_id', data_get($data, 'OrderTrackingId', data_get($data, 'order_tracking_id', '')))
            ->first();

        if (! $subscription) {
            return;
        }

        if (in_array($status, ['completed', 'successful', 'success', 'paid'], true)) {
            $subscription->update([
                'status' => 'active',
                'payment_status' => 'paid',
                'activated_at' => now(),
                'started_at' => now(),
                'renews_at' => now()->addMonth(),
                'callback_received_at' => now(),
            ]);
        } elseif (in_array($status, ['failed', 'declined', 'invalid', 'expired'], true)) {
            $subscription->update([
                'status' => 'inactive',
                'payment_status' => 'failed',
                'callback_received_at' => now(),
            ]);
        }
    }

    public function handlePesapalCallbackPayload(array $payload): bool
    {
        $merchantReference = strtolower((string) ($payload['merchant_reference'] ?? ''));
        $orderTrackingId = (string) ($payload['order_tracking_id'] ?? '');

        if (! str_starts_with($merchantReference, 'buyer_sub_')) {
            return false;
        }

        $subscription = BuyerSubscription::query()
            ->where('reference', $merchantReference)
            ->orWhere('provider_transaction_id', $orderTrackingId)
            ->first();

        if (! $subscription) {
            return false;
        }

        $subscription->update([
            'callback_received_at' => now(),
        ]);

        return true;
    }

    public function verifyPesapalWebhookSignature(string $payload, string $signatureHeader): bool
    {
        $token = (string) config('services.pesapal.webhook_token');
        if ($token === '') {
            return true;
        }

        $expectedSignature = hash_hmac('sha256', $payload, $token);
        $providedSignature = trim($signatureHeader);

        return hash_equals($expectedSignature, $providedSignature);
    }

    private function requestPesapalToken(string $baseUrl, string $consumerKey, string $consumerSecret): string
    {
        $response = Http::post($baseUrl.self::PESAPAL_AUTH_PATH, [
            'consumer_key' => $consumerKey,
            'consumer_secret' => $consumerSecret,
        ]);

        if ($response->failed()) {
            throw ValidationException::withMessages([
                'status' => ['Unable to authenticate with payment provider.'],
            ]);
        }

        return (string) data_get($response->json(), 'token', '');
    }

    private function chargeSubscription(User $user): BuyerSubscription
    {
        return BuyerSubscription::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'plan_code' => self::DEFAULT_PLAN_CODE,
                'amount_ugx' => self::DEFAULT_SUBSCRIPTION_AMOUNT_UGX,
                'currency' => 'UGX',
                'provider' => 'mock',
                'status' => 'active',
                'payment_status' => 'paid',
                'reference' => sprintf('mock_sub_%d', $user->id),
                'started_at' => now(),
                'renews_at' => now()->addMonth(),
                'activated_at' => now(),
                'charged_at' => now(),
            ]
        );
    }
}
