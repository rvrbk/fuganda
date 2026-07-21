<?php

namespace App\Services;

use App\Mail\SellerBillingPaymentMail;
use App\Models\Property;
use App\Models\SellerPublishFee;
use App\Models\SellerSubscription;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class SellerBillingService
{
    private const PROVIDER_PESAPAL = 'pesapal';

    private const DEFAULT_PLAN_CODE = 'starter_monthly';

    private const DEFAULT_SUBSCRIPTION_AMOUNT_UGX = 39000;
    private const DEFAULT_SUBSCRIPTION_AMOUNT_USD = 10;

    private const DEFAULT_PUBLISH_FEE_UGX = 7500;
    private const DEFAULT_PUBLISH_FEE_USD = 2;

    private const PESAPAL_AUTH_PATH = '/api/Auth/RequestToken';
    private const PESAPAL_SUBMIT_ORDER_PATH = '/api/Transactions/SubmitOrderRequest';
    private const PESAPAL_STATUS_PATH = '/api/Transactions/GetTransactionStatus';

    private const NON_PRODUCTION_MIN_SUBSCRIPTION_AMOUNT_UGX = 500;
    private const NON_PRODUCTION_MIN_PUBLISH_FEE_AMOUNT_UGX = 500;

    public function statusFor(User $user): array
    {
        $subscription = $user->sellerSubscription;
        if ($subscription) {
            $subscription = $this->syncPendingPesapalSubscription($subscription);
            $subscription = $this->markSubscriptionPastDueAfterGrace($subscription);
        }

        $hasActiveSubscription = $subscription?->isActive() ?? false;

        $subscriptionAmountUgx = $this->effectiveSubscriptionAmountUgx();

        return [
            'seller_has_active_subscription' => $hasActiveSubscription,
            'seller_subscription_status' => $subscription?->status ?? 'inactive',
            'account_email' => (string) ($user->email ?? ''),
            'subscription' => $subscription,
            'pricing' => [
                'subscription' => [
                    'UGX' => $subscriptionAmountUgx,
                    'USD' => self::DEFAULT_SUBSCRIPTION_AMOUNT_USD,
                ],
                'publish_fee' => [
                    'UGX' => $this->effectivePublishFeeAmountUgx(),
                    'USD' => self::DEFAULT_PUBLISH_FEE_USD,
                ],
            ],
        ];
    }

    /**
     * @param array<string,mixed> $attributes
     * @return array<string,mixed>
     */
    public function requestPublishFeeCheckout(User $user, Property $property, array $attributes = []): array
    {
        if (app()->environment('testing')) {
            $fee = $this->chargePublishFee($user, $property);

            return [
                'paid' => true,
                'fee' => $fee,
            ];
        }

        $existingFee = SellerPublishFee::query()
            ->where('user_id', $user->id)
            ->where('property_id', $property->id)
            ->first();

        $isAlreadyPaid = ($existingFee?->payment_status === 'paid')
            || (in_array((string) ($existingFee?->status ?? ''), ['charged', 'waived'], true)
                && ! in_array((string) ($existingFee?->payment_status ?? ''), ['pending', 'failed', 'expired'], true));

        if ($isAlreadyPaid) {
            return [
                'paid' => true,
                'fee' => $existingFee,
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

        $amount = $this->effectivePublishFeeAmountUgx();
        $providerReference = sprintf('pub_%d_%d_%s', $user->id, $property->id, now()->format('YmdHis'));

        $fee = SellerPublishFee::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'property_id' => $property->id,
            ],
            [
                'provider' => self::PROVIDER_PESAPAL,
                'amount_ugx' => $amount,
                'currency' => 'UGX',
                'payment_method' => $paymentMethod,
                'status' => 'failed',
                'payment_status' => 'pending',
                'reference' => $providerReference,
                'provider_transaction_id' => null,
                'provider_last_event_id' => null,
                'callback_received_at' => null,
                'checkout_session_id' => null,
                'charged_at' => null,
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
            'description' => sprintf('Property publish fee (property #%d)', $property->id),
            'callback_url' => $callbackUrl,
            'notification_id' => $notificationId,
            'branch' => 'verbeek-ug-real-estates',
            'channel' => $paymentMethod === 'mobile_money' ? 'MOBILE' : 'CARD',
            'billing_address' => [
                'email_address' => (string) ($user->email ?? ''),
                'phone_number' => (string) ($attributes['phone_number'] ?? ''),
                'country_code' => 'UG',
                'first_name' => (string) ($user->name ?? 'Seller'),
            ],
            'metadata' => [
                'user_id' => (string) $user->id,
                'property_id' => (string) $property->id,
                'publish_fee_id' => (string) $fee->id,
                'provider' => self::PROVIDER_PESAPAL,
            ],
        ];

        $response = Http::withToken($token)
            ->acceptJson()
            ->post($baseUrl.self::PESAPAL_SUBMIT_ORDER_PATH, $payload);

        if ($response->failed()) {
            $errorMessage = (string) data_get($response->json(), 'error.message', 'Unable to create publish fee checkout session.');

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
                    'checkout_url',
                    data_get(
                        $order,
                        'payment_url',
                        data_get($order, 'data.redirect_url', data_get($order, 'data.checkout_url', data_get($order, 'data.payment_url', '')))
                    )
                )
            )
        );
        $paymentStatus = strtolower((string) data_get($order, 'payment_status', data_get($order, 'data.payment_status', 'pending')));

        if ($redirectUrl === '') {
            $providerError = (string) data_get($order, 'error.message', data_get($order, 'message', ''));
            $fallback = 'Unable to start publish fee checkout. Payment provider did not return a checkout link.';

            throw ValidationException::withMessages([
                'status' => [$providerError !== '' ? $providerError : $fallback],
            ]);
        }

        $fee->fill([
            'reference' => $merchantReference,
            'provider_transaction_id' => $orderTrackingId !== '' ? $orderTrackingId : $fee->provider_transaction_id,
            'checkout_session_id' => $orderTrackingId,
            'payment_status' => $paymentStatus,
            'payment_request_sent_at' => now(),
        ]);
        $fee->save();

        $this->sendSellerPaymentMail(
            $user,
            'Property publish fee payment required',
            'Complete publish fee payment to publish your listing.',
            'Your listing will be published automatically once payment is confirmed.',
            [
                'Property' => (string) ($property->title ?? ('#'.$property->id)),
                'Amount (UGX)' => (string) $amount,
            ],
            $redirectUrl,
            'Pay publish fee'
        );

        return [
            'paid' => false,
            'fee' => $fee->fresh(),
            'checkout_url' => $redirectUrl,
            'checkout_session_id' => $orderTrackingId !== '' ? $orderTrackingId : $merchantReference,
        ];
    }

    public function createCheckoutSession(User $user, array $attributes = []): array
    {
        $paymentMethod = (string) ($attributes['payment_method'] ?? 'mobile_money');
        if (! in_array($paymentMethod, ['mobile_money', 'card'], true)) {
            $paymentMethod = 'mobile_money';
        }

        return $this->createPesapalCheckoutSession($user, $attributes, $paymentMethod);
    }

    private function createPesapalCheckoutSession(User $user, array $attributes, string $paymentMethod): array
    {
        [$currency, $amount, $planCode, $billingEmail, $now, $renewsAt] = $this->resolveCheckoutContext($user, $attributes);

        $providerReference = sprintf('sub_%d_%s', $user->id, now()->format('YmdHis'));

        $subscription = SellerSubscription::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'provider' => self::PROVIDER_PESAPAL,
                'plan_code' => $planCode,
                'amount_ugx' => $amount,
                'currency' => $currency,
                'payment_method' => $paymentMethod,
                'payment_reference_masked' => $this->maskReference($providerReference),
                'provider_reference' => $providerReference,
                'provider_transaction_id' => null,
                'billing_email' => $billingEmail,
                'status' => 'inactive',
                'payment_status' => 'pending',
                'provider_last_event_id' => null,
                'callback_received_at' => null,
                'started_at' => $now,
                'renews_at' => $renewsAt,
                'canceled_at' => null,
                'activated_at' => null,
                'checkout_session_id' => null,
            ]
        );

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
                'payment' => ['Payment service is not configured. Missing: '.implode(', ', $missingConfig).'.'],
            ]);
        }

        $token = $this->requestPesapalToken($baseUrl, $consumerKey, $consumerSecret);

        $callbackUrl = (string) config('services.pesapal.callback_url');
        if ($callbackUrl === '') {
            $callbackUrl = rtrim((string) config('app.url'), '/').'/api/callbacks/pesapal';
        }

        $payload = [
            'id' => $providerReference,
            'currency' => $currency,
            'amount' => $amount,
            'description' => sprintf('Seller subscription (%s)', $planCode),
            'callback_url' => $callbackUrl,
            'notification_id' => $notificationId,
            'branch' => 'verbeek-ug-real-estates',
            'channel' => $paymentMethod === 'mobile_money' ? 'MOBILE' : 'CARD',
            'billing_address' => [
                'email_address' => $billingEmail,
                'phone_number' => (string) ($attributes['phone_number'] ?? ''),
                'country_code' => 'UG',
                'first_name' => (string) ($user->name ?? 'Seller'),
            ],
            'metadata' => [
                'user_id' => (string) $user->id,
                'subscription_id' => (string) $subscription->id,
                'plan_code' => $planCode,
                'selected_payment_method' => $paymentMethod,
                'provider' => self::PROVIDER_PESAPAL,
            ],
        ];

        $response = Http::withToken($token)
            ->acceptJson()
            ->post($baseUrl.self::PESAPAL_SUBMIT_ORDER_PATH, $payload);

        if ($response->failed()) {
            $errorMessage = (string) data_get($response->json(), 'error.message', 'Unable to create Pesapal checkout session.');

            throw ValidationException::withMessages([
                'payment' => [$errorMessage],
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
                    'checkout_url',
                    data_get(
                        $order,
                        'payment_url',
                        data_get($order, 'data.redirect_url', data_get($order, 'data.checkout_url', data_get($order, 'data.payment_url', '')))
                    )
                )
            )
        );
        $paymentStatus = strtolower((string) data_get($order, 'payment_status', data_get($order, 'data.payment_status', 'pending')));

        if ($redirectUrl === '') {
            $providerError = (string) data_get($order, 'error.message', data_get($order, 'message', ''));
            $fallback = 'Unable to start secure checkout. Payment provider did not return a checkout link.';

            throw ValidationException::withMessages([
                'payment' => [$providerError !== '' ? $providerError : $fallback],
            ]);
        }

        $subscription->fill([
            'provider_reference' => $merchantReference,
            'provider_transaction_id' => $orderTrackingId !== '' ? $orderTrackingId : $subscription->provider_transaction_id,
            'payment_reference_masked' => $this->maskReference($merchantReference),
            'checkout_session_id' => $orderTrackingId,
            'payment_status' => $paymentStatus,
        ]);
        $subscription->save();

        return [
            'subscription' => $subscription->fresh(),
            'checkout' => [
                'session_id' => $orderTrackingId !== '' ? $orderTrackingId : $merchantReference,
                'url' => $redirectUrl,
                'payment_status' => $paymentStatus,
                'selected_payment_method' => $paymentMethod,
                'provider' => self::PROVIDER_PESAPAL,
            ],
        ];
    }

    public function verifyPesapalWebhookSignature(string $payload, string $signatureHeader): bool
    {
        $webhookSecret = (string) config('services.pesapal.webhook_secret');
        if ($webhookSecret === '' || trim($signatureHeader) === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $payload, $webhookSecret);

        return hash_equals($expected, trim($signatureHeader));
    }

    public function handlePesapalWebhookPayload(string $payload): void
    {
        $event = json_decode($payload, true);
        if (! is_array($event)) {
            return;
        }

        $this->applyPesapalStatusUpdate($event, true);
    }

    public function handlePesapalCallbackPayload(array $payload): bool
    {
        $merchantReference = (string) data_get($payload, 'merchant_reference', data_get($payload, 'MerchantReference', ''));
        $orderTrackingId = (string) data_get($payload, 'order_tracking_id', data_get($payload, 'OrderTrackingId', ''));

        if ($merchantReference === '' && $orderTrackingId === '') {
            return false;
        }

        $status = $this->fetchPesapalTransactionStatus($merchantReference, $orderTrackingId);
        if ($status === null) {
            return false;
        }

        $event = [
            'event_id' => (string) data_get($status, 'confirmation_code', data_get($status, 'payment_account', $orderTrackingId)),
            'event_type' => (string) data_get($status, 'payment_status_description', data_get($status, 'payment_status', 'pending')),
            'data' => [
                'provider_reference' => $merchantReference !== '' ? $merchantReference : data_get($status, 'merchant_reference', ''),
                'transaction_id' => $orderTrackingId !== '' ? $orderTrackingId : data_get($status, 'order_tracking_id', ''),
                'channel' => data_get($status, 'payment_method'),
                'payment_status' => data_get($status, 'payment_status_description', data_get($status, 'payment_status', 'pending')),
            ],
        ];

        return $this->applyPesapalStatusUpdate($event, false);
    }

    public function verifyMobileMoneyWebhookSignature(string $payload, string $signatureHeader): bool
    {
        return $this->verifyPesapalWebhookSignature($payload, $signatureHeader);
    }

    public function cancel(User $user): SellerSubscription
    {
        $subscription = SellerSubscription::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'plan_code' => self::DEFAULT_PLAN_CODE,
                'amount_ugx' => self::DEFAULT_SUBSCRIPTION_AMOUNT_UGX,
                'currency' => 'UGX',
                'status' => 'inactive',
                'started_at' => now(),
                'renews_at' => now(),
            ]
        );

        $subscription->fill([
            'status' => 'inactive',
            'payment_status' => 'canceled',
            'canceled_at' => now(),
            'renews_at' => now(),
        ]);
        $subscription->save();

        return $subscription;
    }

    private function requestPesapalToken(string $baseUrl, string $consumerKey, string $consumerSecret): string
    {
        $response = Http::acceptJson()->post($baseUrl.self::PESAPAL_AUTH_PATH, [
            'consumer_key' => $consumerKey,
            'consumer_secret' => $consumerSecret,
        ]);

        if ($response->failed()) {
            throw ValidationException::withMessages([
                'pesapal' => ['Unable to authenticate with Pesapal.'],
            ]);
        }

        $token = (string) data_get($response->json(), 'token', data_get($response->json(), 'access_token', ''));
        if ($token === '') {
            $errorCode = (string) data_get($response->json(), 'error.code', '');
            $errorMessage = (string) data_get($response->json(), 'error.message', '');
            $details = trim($errorCode !== '' ? $errorCode.($errorMessage !== '' ? ': '.$errorMessage : '') : $errorMessage);

            throw ValidationException::withMessages([
                'pesapal' => [
                    $details !== ''
                        ? 'Unable to authenticate with Pesapal ('.$details.').'
                        : 'Pesapal authentication token is missing.',
                ],
            ]);
        }

        return $token;
    }

    private function resolveCheckoutContext(User $user, array $attributes): array
    {
        $now = now();
        $renewsAt = $now->copy()->addMonth();
        $currency = 'UGX';

        $defaultAmount = $this->effectiveSubscriptionAmountUgx();

        $amount = (int) ($attributes['amount_ugx'] ?? $defaultAmount);
        if (! app()->isProduction()) {
            $amount = $defaultAmount;
        }

        $billingEmail = (string) ($attributes['billing_email'] ?? $user->email);
        $planCode = (string) ($attributes['plan_code'] ?? self::DEFAULT_PLAN_CODE);

        return [$currency, $amount, $planCode, $billingEmail, $now, $renewsAt];
    }

    private function effectivePublishFeeAmountUgx(): int
    {
        if (app()->isProduction()) {
            return (int) config('services.pesapal.publish_fee_amount_ugx', self::DEFAULT_PUBLISH_FEE_UGX);
        }

        $configured = (int) config(
            'services.pesapal.non_production_min_publish_fee_amount_ugx',
            self::NON_PRODUCTION_MIN_PUBLISH_FEE_AMOUNT_UGX
        );

        return max(1, $configured);
    }

    private function subscriptionGracePeriodDays(): int
    {
        return max(0, (int) config('services.pesapal.subscription_grace_period_days', 7));
    }

    private function markSubscriptionPastDueAfterGrace(SellerSubscription $subscription): SellerSubscription
    {
        if ($subscription->status !== 'active' || ! $subscription->renews_at) {
            return $subscription;
        }

        $graceBoundary = $subscription->renews_at->copy()->addDays($this->subscriptionGracePeriodDays());
        if (! now()->greaterThan($graceBoundary)) {
            return $subscription;
        }

        $subscription->fill([
            'status' => 'past_due',
            'payment_status' => 'overdue',
        ]);
        $subscription->save();

        return $subscription->fresh() ?? $subscription;
    }

    private function effectiveSubscriptionAmountUgx(): int
    {
        if (app()->isProduction()) {
            return self::DEFAULT_SUBSCRIPTION_AMOUNT_UGX;
        }

        $configured = (int) config(
            'services.pesapal.non_production_min_subscription_amount_ugx',
            self::NON_PRODUCTION_MIN_SUBSCRIPTION_AMOUNT_UGX
        );

        return max(1, $configured);
    }

    private function syncPendingPesapalSubscription(SellerSubscription $subscription): SellerSubscription
    {
        $provider = strtolower((string) ($subscription->provider ?? ''));
        $paymentStatus = strtolower((string) ($subscription->payment_status ?? ''));
        $pendingStates = ['pending', 'processing', 'requires_action', 'queued'];

        if ($provider !== self::PROVIDER_PESAPAL || ! in_array($paymentStatus, $pendingStates, true)) {
            return $subscription;
        }

        $merchantReference = (string) ($subscription->provider_reference ?? '');
        $orderTrackingId = (string) ($subscription->provider_transaction_id ?? $subscription->checkout_session_id ?? '');
        if ($merchantReference === '' && $orderTrackingId === '') {
            return $subscription;
        }

        $status = $this->fetchPesapalTransactionStatus($merchantReference, $orderTrackingId);
        if ($status === null) {
            return $subscription;
        }

        $event = [
            'event_id' => (string) data_get($status, 'confirmation_code', data_get($status, 'payment_account', $orderTrackingId)),
            'event_type' => (string) data_get($status, 'payment_status_description', data_get($status, 'payment_status', 'pending')),
            'data' => [
                'provider_reference' => $merchantReference !== '' ? $merchantReference : data_get($status, 'merchant_reference', ''),
                'transaction_id' => $orderTrackingId !== '' ? $orderTrackingId : data_get($status, 'order_tracking_id', ''),
                'channel' => data_get($status, 'payment_method'),
                'payment_status' => data_get($status, 'payment_status_description', data_get($status, 'payment_status', 'pending')),
            ],
        ];

        $this->applyPesapalStatusUpdate($event, false);

        return $subscription->fresh() ?? $subscription;
    }

    private function maskReference(string $value): string
    {
        if (strlen($value) <= 6) {
            return str_repeat('*', strlen($value));
        }

        return substr($value, 0, 3).str_repeat('*', max(strlen($value) - 6, 1)).substr($value, -3);
    }

    public function enforcePublishRequirements(User $user): void
    {
        if (config('app.demo_mode')) {
            return;
        }

        if ($user->isAdmin() || ! $user->isSeller()) {
            return;
        }

        $subscription = $user->sellerSubscription;
        if ($subscription) {
            $subscription = $this->markSubscriptionPastDueAfterGrace($subscription);
        }

        if (! $subscription?->isActive()) {
            throw ValidationException::withMessages([
                'status' => ['An active seller subscription is required to publish a property.'],
            ]);
        }
    }

    public function chargePublishFee(User $user, Property $property): SellerPublishFee
    {
        $billingCurrency = 'UGX';

        $publishFeeAmount = $this->effectivePublishFeeAmountUgx();

        return SellerPublishFee::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'property_id' => $property->id,
            ],
            [
                'provider' => 'manual',
                'amount_ugx' => $publishFeeAmount,
                'currency' => $billingCurrency,
                'payment_method' => 'manual',
                'status' => 'charged',
                'payment_status' => 'paid',
                'charged_at' => now(),
                'reference' => sprintf('mock_fee_%d_%d', $user->id, $property->id),
            ]
        );
    }

    public function hasActiveSubscription(User $user): bool
    {
        $subscription = $user->sellerSubscription;
        if ($subscription) {
            $subscription = $this->markSubscriptionPastDueAfterGrace($subscription);
        }

        return $subscription?->isActive() ?? false;
    }

    private function nonProductionMockPaymentStatus(): string
    {
        if (app()->isProduction()) {
            return '';
        }

        $configured = strtolower(trim((string) config('services.pesapal.non_production_mock_payment_status', '')));
        if ($configured === '') {
            return '';
        }

        return match ($configured) {
            'paid', 'success', 'succeeded', 'complete', 'completed' => 'paid',
            'failed', 'fail', 'declined', 'invalid' => 'failed',
            'pending', 'processing', 'queued' => 'pending',
            'expired' => 'expired',
            'canceled', 'cancelled' => 'canceled',
            default => '',
        };
    }

    /**
     * @return array<string,string>
     */
    private function buildMockPesapalStatus(string $mockStatus, string $merchantReference, string $orderTrackingId): array
    {
        $description = match ($mockStatus) {
            'paid' => 'COMPLETED',
            'failed' => 'INVALID',
            'expired' => 'EXPIRED',
            'canceled' => 'CANCELLED',
            default => 'PENDING',
        };

        return [
            'payment_status_description' => $description,
            'payment_status' => $description,
            'merchant_reference' => $merchantReference,
            'order_tracking_id' => $orderTrackingId,
        ];
    }

    private function fetchPesapalTransactionStatus(string $merchantReference, string $orderTrackingId): ?array
    {
        $mockStatus = $this->nonProductionMockPaymentStatus();
        if ($mockStatus !== '') {
            return $this->buildMockPesapalStatus($mockStatus, $merchantReference, $orderTrackingId);
        }

        $baseUrl = rtrim((string) config('services.pesapal.base_url'), '/');
        $consumerKey = (string) config('services.pesapal.consumer_key');
        $consumerSecret = (string) config('services.pesapal.consumer_secret');

        if ($baseUrl === '' || $consumerKey === '' || $consumerSecret === '') {
            return null;
        }

        $token = $this->requestPesapalToken($baseUrl, $consumerKey, $consumerSecret);

        $query = [];
        if ($merchantReference !== '') {
            $query['merchantReference'] = $merchantReference;
        }
        if ($orderTrackingId !== '') {
            $query['orderTrackingId'] = $orderTrackingId;
        }

        $response = Http::withToken($token)
            ->acceptJson()
            ->get($baseUrl.self::PESAPAL_STATUS_PATH, $query);

        if ($response->failed()) {
            $errorCode = strtolower((string) data_get($response->json(), 'error.code', ''));
            if ($response->status() === 404 || $errorCode === 'payment_details_not_found') {
                return [
                    'payment_status_description' => 'INVALID',
                    'merchant_reference' => $merchantReference,
                    'order_tracking_id' => $orderTrackingId,
                ];
            }

            return null;
        }

        $status = $response->json();

        return is_array($status) ? $status : null;
    }

    private function applyPesapalStatusUpdate(array $event, bool $allowSubscriptionIdLookup): bool
    {
        $eventId = (string) data_get($event, 'event_id', data_get($event, 'id', ''));
        $eventType = strtolower((string) data_get($event, 'event_type', data_get($event, 'type', 'pending')));

        $subscriptionId = $allowSubscriptionIdLookup
            ? (int) data_get($event, 'data.subscription_id', data_get($event, 'subscription_id', 0))
            : 0;
        $providerReference = (string) data_get($event, 'data.provider_reference', data_get($event, 'provider_reference', data_get($event, 'data.merchant_reference', '')));
        $transactionId = (string) data_get($event, 'data.transaction_id', data_get($event, 'transaction_id', data_get($event, 'data.order_tracking_id', '')));

        $subscriptionQuery = SellerSubscription::query();

        if ($subscriptionId > 0) {
            $subscriptionQuery->where('id', $subscriptionId);
        } elseif ($providerReference !== '') {
            $subscriptionQuery->where('provider_reference', $providerReference);
            if ($transactionId !== '') {
                $subscriptionQuery->orWhere('provider_transaction_id', $transactionId);
            }
        } elseif ($transactionId !== '') {
            $subscriptionQuery->where('provider_transaction_id', $transactionId);
        }

        $subscription = $subscriptionQuery->first();

        if (! $subscription) {
            return $this->applyPesapalPublishFeeStatusUpdate($event, $providerReference, $transactionId, $eventType, $eventId);
        }

        if ($eventId !== '' && $subscription->provider_last_event_id === $eventId) {
            return true;
        }

        $channel = strtolower((string) data_get($event, 'data.channel', data_get($event, 'channel', data_get($event, 'data.payment_method', $subscription->payment_method ?? 'mobile_money'))));
        $paymentMethod = str_contains($channel, 'card') ? 'card' : 'mobile_money';

        $subscription->fill([
            'provider' => self::PROVIDER_PESAPAL,
            'provider_last_event_id' => $eventId,
            'provider_reference' => $providerReference !== '' ? $providerReference : $subscription->provider_reference,
            'provider_transaction_id' => $transactionId !== '' ? $transactionId : $subscription->provider_transaction_id,
            'callback_received_at' => now(),
            'payment_method' => $paymentMethod,
            'checkout_session_id' => $transactionId !== '' ? $transactionId : $subscription->checkout_session_id,
        ]);

        if (str_contains($eventType, 'success') || str_contains($eventType, 'succeed') || str_contains($eventType, 'complete') || str_contains($eventType, 'paid')) {
            $subscription->fill([
                'status' => 'active',
                'payment_status' => 'paid',
                'activated_at' => now(),
                'started_at' => now(),
                'renews_at' => now()->addMonth(),
                'canceled_at' => null,
            ]);

            $this->sendSellerPaymentMail(
                $subscription->user,
                'Subscription payment confirmed',
                'Subscription payment received.',
                'Your seller subscription remains active for the next cycle.',
                [
                    'Amount (UGX)' => (string) $subscription->amount_ugx,
                    'Next renewal date' => (string) now()->addMonth()->toDateString(),
                ]
            );
        }

        if (str_contains($eventType, 'fail') || str_contains($eventType, 'declin') || str_contains($eventType, 'invalid')) {
            $subscription->fill([
                'status' => 'past_due',
                'payment_status' => 'failed',
                'activated_at' => null,
            ]);

            $this->sendSellerPaymentMail(
                $subscription->user,
                'Subscription payment failed',
                'Subscription payment could not be completed.',
                'Please retry payment to keep publishing access active.',
                [
                    'Amount (UGX)' => (string) $subscription->amount_ugx,
                ],
                rtrim((string) config('app.url'), '/').'/seller/onboarding',
                'Retry subscription payment'
            );
        }

        if (str_contains($eventType, 'expired') || str_contains($eventType, 'cancel')) {
            $subscription->fill([
                'status' => 'inactive',
                'payment_status' => 'expired',
                'activated_at' => null,
            ]);

            $this->sendSellerPaymentMail(
                $subscription->user,
                'Subscription expired',
                'Your subscription payment window has expired.',
                'Complete payment to resume publishing properties.',
                [
                    'Amount (UGX)' => (string) $subscription->amount_ugx,
                ],
                rtrim((string) config('app.url'), '/').'/seller/onboarding',
                'Resume subscription'
            );
        }

        $subscription->save();

        return true;
    }

    private function applyPesapalPublishFeeStatusUpdate(
        array $event,
        string $providerReference,
        string $transactionId,
        string $eventType,
        string $eventId,
    ): bool {
        $feeQuery = SellerPublishFee::query();

        if ($providerReference !== '') {
            $feeQuery->where('reference', $providerReference);
            if ($transactionId !== '') {
                $feeQuery->orWhere('provider_transaction_id', $transactionId)
                    ->orWhere('checkout_session_id', $transactionId);
            }
        } elseif ($transactionId !== '') {
            $feeQuery->where('provider_transaction_id', $transactionId)
                ->orWhere('checkout_session_id', $transactionId);
        }

        $fee = $feeQuery->first();
        if (! $fee) {
            return false;
        }

        if ($eventId !== '' && $fee->provider_last_event_id === $eventId) {
            return true;
        }

        $channel = strtolower((string) data_get($event, 'data.channel', data_get($event, 'channel', data_get($event, 'data.payment_method', $fee->payment_method ?? 'mobile_money'))));
        $paymentMethod = str_contains($channel, 'card') ? 'card' : 'mobile_money';

        $fee->fill([
            'provider' => self::PROVIDER_PESAPAL,
            'provider_last_event_id' => $eventId,
            'reference' => $providerReference !== '' ? $providerReference : $fee->reference,
            'provider_transaction_id' => $transactionId !== '' ? $transactionId : $fee->provider_transaction_id,
            'callback_received_at' => now(),
            'payment_method' => $paymentMethod,
            'checkout_session_id' => $transactionId !== '' ? $transactionId : $fee->checkout_session_id,
        ]);

        if (str_contains($eventType, 'success') || str_contains($eventType, 'succeed') || str_contains($eventType, 'complete') || str_contains($eventType, 'paid')) {
            $fee->fill([
                'status' => 'charged',
                'payment_status' => 'paid',
                'charged_at' => now(),
            ]);

            $property = $fee->property;
            if ($property && $property->status !== 'published') {
                $property->fill([
                    'status' => 'published',
                    'published_at' => now(),
                ]);
                $property->save();
            }

            $this->sendSellerPaymentMail(
                $fee->user,
                'Property publish fee payment confirmed',
                'Property publish fee paid successfully.',
                'Your property listing is now published.',
                [
                    'Property ID' => (string) $fee->property_id,
                    'Amount (UGX)' => (string) $fee->amount_ugx,
                ]
            );
        }

        if (str_contains($eventType, 'fail') || str_contains($eventType, 'declin') || str_contains($eventType, 'invalid') || str_contains($eventType, 'expired') || str_contains($eventType, 'cancel')) {
            $fee->fill([
                'status' => 'failed',
                'payment_status' => 'failed',
                'charged_at' => null,
            ]);

            $this->sendSellerPaymentMail(
                $fee->user,
                'Property publish fee payment failed',
                'Property publish fee payment was not completed.',
                'Please retry payment from your listing flow to publish.',
                [
                    'Property ID' => (string) $fee->property_id,
                    'Amount (UGX)' => (string) $fee->amount_ugx,
                ]
            );
        }

        if (
            ! str_contains($eventType, 'success')
            && ! str_contains($eventType, 'succeed')
            && ! str_contains($eventType, 'complete')
            && ! str_contains($eventType, 'paid')
            && ! str_contains($eventType, 'fail')
            && ! str_contains($eventType, 'declin')
            && ! str_contains($eventType, 'invalid')
            && ! str_contains($eventType, 'expired')
            && ! str_contains($eventType, 'cancel')
        ) {
            $fee->fill([
                'payment_status' => 'pending',
            ]);
        }

        $fee->save();

        return true;
    }

    public function sendMonthlySubscriptionPaymentRequests(): int
    {
        $sentCount = 0;
        $graceDays = $this->subscriptionGracePeriodDays();

        $subscriptions = SellerSubscription::query()
            ->with('user:id,email,name')
            ->whereIn('status', ['active', 'past_due'])
            ->whereNotNull('renews_at')
            ->where('renews_at', '<=', now())
            ->get();

        foreach ($subscriptions as $subscription) {
            if (! $subscription instanceof SellerSubscription) {
                continue;
            }

            $user = $subscription->user;
            if (! $user || empty($user->email)) {
                continue;
            }

            $renewalMoment = $subscription->renews_at;
            if (! $renewalMoment) {
                continue;
            }

            $isOverdue = now()->greaterThan($renewalMoment->copy()->addDays($graceDays));
            $onboardingUrl = rtrim((string) config('app.url'), '/').'/seller/onboarding';

            if ($isOverdue) {
                $wasAlreadyNotified = $subscription->overdue_notification_sent_at !== null
                    && $subscription->overdue_notification_sent_at->greaterThanOrEqualTo($renewalMoment);

                if ($subscription->status !== 'past_due') {
                    $subscription->fill([
                        'status' => 'past_due',
                        'payment_status' => 'overdue',
                    ]);
                }

                if (! $wasAlreadyNotified) {
                    $this->sendSellerPaymentMail(
                        $user,
                        'Subscription payment overdue',
                        'Your monthly subscription payment is overdue.',
                        'Posting new properties is blocked until payment is completed.',
                        [
                            'Subscription amount (UGX)' => (string) $subscription->amount_ugx,
                            'Grace period (days)' => (string) $graceDays,
                        ],
                        $onboardingUrl,
                        'Pay subscription now'
                    );

                    $subscription->overdue_notification_sent_at = Carbon::now();
                    $sentCount++;
                }

                $subscription->save();
                continue;
            }

            $alreadySentForCycle = $subscription->payment_request_sent_at !== null
                && $subscription->payment_request_sent_at->greaterThanOrEqualTo($renewalMoment);

            if ($alreadySentForCycle) {
                continue;
            }

            $this->sendSellerPaymentMail(
                $user,
                'Monthly subscription payment request',
                'Your monthly seller subscription is due.',
                'Please complete payment to keep uninterrupted access to publishing.',
                [
                    'Subscription amount (UGX)' => (string) $subscription->amount_ugx,
                    'Renewal date' => (string) $renewalMoment->toDateString(),
                ],
                $onboardingUrl,
                'Pay subscription now'
            );

            $subscription->payment_request_sent_at = Carbon::now();
            $subscription->save();
            $sentCount++;
        }

        return $sentCount;
    }

    /**
     * @param array<string,string> $payload
     */
    private function sendSellerPaymentMail(
        ?User $user,
        string $subject,
        string $headline,
        string $message,
        array $payload = [],
        ?string $actionUrl = null,
        ?string $actionLabel = null,
    ): void {
        if (! $user || empty($user->email)) {
            return;
        }

        try {
            Mail::to($user->email)->send(new SellerBillingPaymentMail(
                $subject,
                $headline,
                $message,
                $payload,
                $actionUrl,
                $actionLabel,
            ));
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
