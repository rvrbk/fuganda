<?php

namespace App\Services;

use App\Mail\BuyerBillingPaymentMail;
use App\Models\BuyerSubscription;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class BuyerBillingService
{
    public const PROVIDER_MTN = 'mtn';
    public const PROVIDER_AIRTEL = 'airtel';
    public const PROVIDER_MOBILE_MONEY = 'mobile_money';

    private const DEFAULT_PLAN_CODE = 'buyer_monthly';

    private const DEFAULT_SUBSCRIPTION_AMOUNT_UGX = 10000;
    private const DEFAULT_SUBSCRIPTION_AMOUNT_USD = 3;

    public function __construct(
        private readonly MtnMomoService $mtnMomoService,
        private readonly AirtelMoneyService $airtelMoneyService
    ) {
    }

    public function statusFor(User $user): array
    {
        $subscription = $user->buyerSubscription;
        if ($subscription) {
            $subscription = $this->syncPendingMobileMoneySubscription($subscription);
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

        // Validate payment provider
        $paymentProvider = (string) ($attributes['payment_provider'] ?? ($attributes['payment_method'] ?? 'mtn'));
        $phoneNumber = (string) ($attributes['phone_number'] ?? '');

        // Normalize provider - accept mtn, airtel, or mobile_money (defaults to mtn)
        if (! in_array($paymentProvider, [self::PROVIDER_MTN, self::PROVIDER_AIRTEL, self::PROVIDER_MOBILE_MONEY], true)) {
            $paymentProvider = (string) config('services.mobile_money.default_provider', self::PROVIDER_MTN);
        }

        // If mobile_money is specified, use the default provider
        if ($paymentProvider === self::PROVIDER_MOBILE_MONEY) {
            $paymentProvider = (string) config('services.mobile_money.default_provider', self::PROVIDER_MTN);
        }

        // If phone number is not provided but we're in testing or demo mode, use a mock
        if ($phoneNumber === '') {
            if (app()->environment('local') || app()->environment('testing')) {
                $phoneNumber = '256701000000'; // Mock phone number for testing
            } else {
                throw ValidationException::withMessages([
                    'phone_number' => ['Phone number is required for mobile money payments.'],
                ]);
            }
        }

        $amount = $this->effectiveSubscriptionAmountUgx();
        $providerReference = sprintf('buyer_sub_%d_%s', $user->id, now()->format('YmdHis'));

        $subscription = BuyerSubscription::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'plan_code' => self::DEFAULT_PLAN_CODE,
                'amount_ugx' => $amount,
                'currency' => 'UGX',
                'provider' => $paymentProvider,
                'status' => 'inactive',
                'payment_status' => 'pending',
                'reference' => $providerReference,
                'provider_transaction_id' => null,
                'provider_last_event_id' => null,
                'callback_received_at' => null,
                'checkout_session_id' => null,
                'billing_email' => $attributes['billing_email'] ?? $user->email ?? null,
                'payment_method' => 'mobile_money',
                'payment_request_sent_at' => now(),
            ]
        );

        // Initialize payment with the selected provider
        $result = $this->initiateMobileMoneySubscription(
            $paymentProvider,
            $phoneNumber,
            $amount,
            $providerReference,
            sprintf('Buyer subscription for %s', $user->name ?? 'User'),
            $subscription
        );

        return [
            'subscription' => $subscription->fresh(),
            'checkout' => [
                'url' => null, // No redirect URL for mobile money - payment is direct
                'payment_status' => 'pending',
                'order_tracking_id' => $result['provider_transaction_id'],
                'merchant_reference' => $providerReference,
                'provider' => $paymentProvider,
                'message' => $result['message'] ?? 'Payment request sent successfully',
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

    /**
     * Sync pending mobile money subscription by checking with the provider
     */
    public function syncPendingMobileMoneySubscription(BuyerSubscription $subscription): BuyerSubscription
    {
        if ($subscription->status !== 'pending' && $subscription->status !== 'inactive') {
            return $subscription;
        }

        if ($subscription->payment_status !== 'pending') {
            return $subscription;
        }

        $provider = $subscription->provider;
        $transactionId = $subscription->provider_transaction_id;

        if ($transactionId === null) {
            return $subscription;
        }

        try {
            $statusResult = $this->checkMobileMoneyTransactionStatus($provider, $transactionId);
            $status = $statusResult['status'];

            if (in_array($status, ['paid', 'completed', 'successful', 'success'], true)) {
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
            } elseif (in_array($status, ['failed', 'declined', 'invalid', 'expired', 'rejected'], true)) {
                $subscription->update([
                    'status' => 'inactive',
                    'payment_status' => 'failed',
                    'callback_received_at' => now(),
                ]);
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

    /**
     * Handle MTN MoMo webhook for subscriptions
     */
    public function handleMtnWebhook(string $payload, string $signatureHeader): array
    {
        if (! $this->mtnMomoService->verifyWebhookSignature($payload, $signatureHeader)) {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException('Invalid MTN MoMo webhook signature.');
        }

        $data = json_decode($payload, true);
        if (! is_array($data)) {
            return ['processed' => false, 'error' => 'Invalid payload'];
        }

        return $this->processMobileMoneyWebhook(self::PROVIDER_MTN, $data);
    }

    /**
     * Handle Airtel Money webhook for subscriptions
     */
    public function handleAirtelWebhook(string $payload, string $signatureHeader): array
    {
        if (! $this->airtelMoneyService->verifyWebhookSignature($payload, $signatureHeader)) {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException('Invalid Airtel Money webhook signature.');
        }

        $data = json_decode($payload, true);
        if (! is_array($data)) {
            return ['processed' => false, 'error' => 'Invalid payload'];
        }

        return $this->processMobileMoneyWebhook(self::PROVIDER_AIRTEL, $data);
    }

    /**
     * Process webhook from mobile money providers for subscriptions
     */
    private function processMobileMoneyWebhook(string $provider, array $data): array
    {
        // Extract reference based on provider
        $reference = match ($provider) {
            self::PROVIDER_MTN => data_get($data, 'externalId', data_get($data, 'reference', '')),
            self::PROVIDER_AIRTEL => data_get($data, 'reference', data_get($data, 'Reference', '')),
            default => '',
        };

        $status = strtolower((string) data_get($data, 'status', data_get($data, 'Status', '')));
        $transactionId = match ($provider) {
            self::PROVIDER_MTN => data_get($data, 'referenceId', data_get($data, 'transactionId', '')),
            self::PROVIDER_AIRTEL => data_get($data, 'transactionId', data_get($data, 'TransactionId', '')),
            default => '',
        };

        // Find the subscription by reference
        $subscription = BuyerSubscription::query()
            ->where('reference', $reference)
            ->orWhere('provider_transaction_id', $transactionId)
            ->first();

        if (! $subscription) {
            Log::warning('Mobile money webhook: Subscription not found', [
                'provider' => $provider,
                'reference' => $reference,
                'transaction_id' => $transactionId,
            ]);

            return ['processed' => false, 'error' => 'Subscription not found'];
        }

        // Normalize status
        $normalizedStatus = match ($provider) {
            self::PROVIDER_MTN => $this->mtnMomoService->normalizeStatus($status),
            self::PROVIDER_AIRTEL => $this->airtelMoneyService->normalizeStatus($status),
            default => 'unknown',
        };

        if (in_array($normalizedStatus, ['paid', 'completed', 'successful', 'success'], true)) {
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
        } elseif (in_array($normalizedStatus, ['failed', 'declined', 'invalid', 'expired', 'rejected'], true)) {
            $subscription->update([
                'status' => 'inactive',
                'payment_status' => 'failed',
                'callback_received_at' => now(),
            ]);
        }

        return ['processed' => true, 'subscription_id' => $subscription->id];
    }

    /**
     * Check transaction status with the mobile money provider
     */
    private function checkMobileMoneyTransactionStatus(string $provider, string $transactionId): array
    {
        return match ($provider) {
            self::PROVIDER_MTN => $this->mtnMomoService->checkTransactionStatus($transactionId, false),
            self::PROVIDER_AIRTEL => $this->airtelMoneyService->checkTransactionStatus($transactionId, false),
            default => ['status' => 'UNKNOWN', 'message' => 'Unknown provider'],
        };
    }

    /**
     * Initiate mobile money subscription payment based on provider
     */
    private function initiateMobileMoneySubscription(
        string $provider,
        string $phoneNumber,
        int $amount,
        string $reference,
        string $description,
        BuyerSubscription $subscription
    ): array {
        return match ($provider) {
            self::PROVIDER_MTN => $this->processMtnSubscription($phoneNumber, $amount, $reference, $description, $subscription),
            self::PROVIDER_AIRTEL => $this->processAirtelSubscription($phoneNumber, $amount, $reference, $description, $subscription),
            default => throw ValidationException::withMessages([
                'status' => ['Unsupported payment provider: ' . $provider],
            ]),
        };
    }

    /**
     * Process MTN MoMo subscription payment
     */
    private function processMtnSubscription(
        string $phoneNumber,
        int $amount,
        string $reference,
        string $description,
        BuyerSubscription $subscription
    ): array {
        try {
            $result = $this->mtnMomoService->requestToPay(
                $phoneNumber,
                $amount,
                $reference,
                $description,
                false // Use sandbox for now
            );

            // Update subscription with provider transaction ID
            $subscription->update([
                'provider_transaction_id' => $result['provider_transaction_id'],
                'checkout_session_id' => $reference,
                'status' => 'pending',
            ]);

            return [
                'provider_transaction_id' => $result['provider_transaction_id'],
                'message' => 'MTN MoMo subscription payment request sent successfully',
            ];
        } catch (\Exception $e) {
            report($e);
            throw ValidationException::withMessages([
                'status' => ['MTN MoMo subscription payment failed: ' . $e->getMessage()],
            ]);
        }
    }

    /**
     * Process Airtel Money subscription payment
     */
    private function processAirtelSubscription(
        string $phoneNumber,
        int $amount,
        string $reference,
        string $description,
        BuyerSubscription $subscription
    ): array {
        try {
            $result = $this->airtelMoneyService->collect(
                $phoneNumber,
                $amount,
                $reference,
                $description,
                false // Use sandbox for now
            );

            // Update subscription with provider transaction ID
            $subscription->update([
                'provider_transaction_id' => $result['provider_transaction_id'],
                'checkout_session_id' => $reference,
                'status' => 'pending',
            ]);

            return [
                'provider_transaction_id' => $result['provider_transaction_id'],
                'message' => 'Airtel Money subscription payment request sent successfully',
            ];
        } catch (\Exception $e) {
            report($e);
            throw ValidationException::withMessages([
                'status' => ['Airtel Money subscription payment failed: ' . $e->getMessage()],
            ]);
        }
    }

    /**
     * Handle callback for mobile money (for non-webhook callbacks)
     */
    public function handleMobileMoneyCallback(array $payload): bool
    {
        $reference = strtolower((string) ($payload['merchant_reference'] ?? $payload['reference'] ?? ''));
        $orderTrackingId = (string) ($payload['order_tracking_id'] ?? $payload['transaction_id'] ?? '');

        if (! str_starts_with($reference, 'buyer_sub_')) {
            return false;
        }

        $subscription = BuyerSubscription::query()
            ->where('reference', $reference)
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
