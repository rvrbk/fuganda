<?php

namespace App\Services;

use App\Mail\SellerBillingPaymentMail;
use App\Models\BuyerContactFee;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class BuyerContactService
{
    public const PROVIDER_MTN = 'mtn';
    public const PROVIDER_AIRTEL = 'airtel';
    public const PROVIDER_MOBILE_MONEY = 'mobile_money';

    private const CONTACT_FEE_AMOUNT_UGX = 10000;
    private const CONTACT_FEE_AMOUNT_USD = 3;

    public function __construct(
        private readonly MtnMomoService $mtnMomoService,
        private readonly AirtelMoneyService $airtelMoneyService
    ) {
    }

    public function effectiveContactFeeAmountUgx(): int
    {
        return self::CONTACT_FEE_AMOUNT_UGX;
    }

    public function hasPaidForProperty(User $user, Property $property): bool
    {
        $fee = BuyerContactFee::query()
            ->where('user_id', $user->id)
            ->where('property_id', $property->id)
            ->first();

        if ($fee) {
            $fee = $this->syncPendingMobileMoneyFee($fee);
        }

        return $fee?->isPaid() ?? false;
    }

    public function requestContactFeeCheckout(User $user, Property $property, array $attributes = []): array
    {
        if (app()->environment('testing')) {
            $fee = $this->chargeContactFee($user, $property);

            return [
                'paid' => true,
                'fee' => $fee,
            ];
        }

        $existingFee = BuyerContactFee::query()
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
                $phoneNumber = '256772000000'; // Mock phone number for testing
            } else {
                throw ValidationException::withMessages([
                    'phone_number' => ['Phone number is required for mobile money payments.'],
                ]);
            }
        }

        $amount = $this->effectiveContactFeeAmountUgx();
        $providerReference = sprintf('contact_%d_%d_%s', $user->id, $property->id, now()->format('YmdHis'));

        // Create the fee record
        $fee = BuyerContactFee::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'property_id' => $property->id,
            ],
            [
                'provider' => $paymentProvider,
                'amount_ugx' => $amount,
                'currency' => 'UGX',
                'payment_method' => 'mobile_money',
                'status' => 'failed',
                'payment_status' => 'pending',
                'reference' => $providerReference,
                'provider_transaction_id' => null,
                'provider_last_event_id' => null,
                'callback_received_at' => null,
                'checkout_session_id' => null,
                'billing_email' => $attributes['billing_email'] ?? $user->email ?? null,
                'payment_request_sent_at' => now(),
            ]
        );

        // Initialize payment with the selected provider
        $result = $this->initiateMobileMoneyPayment(
            $paymentProvider,
            $phoneNumber,
            $amount,
            $providerReference,
            sprintf('Contact seller for property #%d', $property->id),
            $fee
        );

        return [
            'fee' => $fee->fresh(),
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

    /**
     * Sync pending mobile money fee by checking with the provider
     */
    public function syncPendingMobileMoneyFee(BuyerContactFee $fee): BuyerContactFee
    {
        if ($fee->status === 'charged' || $fee->payment_status === 'paid') {
            return $fee;
        }

        if ($fee->status !== 'failed' || $fee->payment_status !== 'pending') {
            return $fee;
        }

        $provider = $fee->provider;
        $transactionId = $fee->provider_transaction_id;

        if ($transactionId === null) {
            return $fee;
        }

        try {
            $statusResult = $this->checkMobileMoneyTransactionStatus($provider, $transactionId);
            $status = $statusResult['status'];

            if (in_array($status, ['paid', 'completed', 'successful', 'success'], true)) {
                $fee->update([
                    'status' => 'charged',
                    'payment_status' => 'paid',
                    'charged_at' => now(),
                    'callback_received_at' => now(),
                ]);

                try {
                    if (! empty($fee->billing_email)) {
                        Mail::to($fee->billing_email)->send(new SellerBillingPaymentMail(
                            'Contact Fee Payment Received',
                            'Contact fee paid successfully',
                            sprintf('You have successfully paid the contact fee for property #%d. You can now contact the seller.', $fee->property_id),
                            ['contact_fee_id' => $fee->id],
                            null,
                            null
                        ));
                    }
                } catch (\Throwable $e) {
                    report($e);
                }
            } elseif (in_array($status, ['failed', 'declined', 'invalid', 'expired', 'rejected'], true)) {
                $fee->update([
                    'status' => 'failed',
                    'payment_status' => 'failed',
                    'callback_received_at' => now(),
                ]);
            }
        } catch (\Exception $e) {
            report($e);
        }

        return $fee;
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
     * Initiate mobile money payment based on provider
     */
    private function initiateMobileMoneyPayment(
        string $provider,
        string $phoneNumber,
        int $amount,
        string $reference,
        string $description,
        BuyerContactFee $fee
    ): array {
        return match ($provider) {
            self::PROVIDER_MTN => $this->processMtnPayment($phoneNumber, $amount, $reference, $description, $fee),
            self::PROVIDER_AIRTEL => $this->processAirtelPayment($phoneNumber, $amount, $reference, $description, $fee),
            default => throw ValidationException::withMessages([
                'status' => ['Unsupported payment provider: ' . $provider],
            ]),
        };
    }

    /**
     * Process MTN MoMo payment
     */
    private function processMtnPayment(
        string $phoneNumber,
        int $amount,
        string $reference,
        string $description,
        BuyerContactFee $fee
    ): array {
        try {
            $result = $this->mtnMomoService->requestToPay(
                $phoneNumber,
                $amount,
                $reference,
                $description,
                false // Use sandbox for now
            );

            // Update fee with provider transaction ID
            $fee->update([
                'provider_transaction_id' => $result['provider_transaction_id'],
                'checkout_session_id' => $reference,
            ]);

            return [
                'provider_transaction_id' => $result['provider_transaction_id'],
                'message' => 'MTN MoMo payment request sent successfully',
            ];
        } catch (\Exception $e) {
            report($e);
            throw ValidationException::withMessages([
                'status' => ['MTN MoMo payment failed: ' . $e->getMessage()],
            ]);
        }
    }

    /**
     * Process Airtel Money payment
     */
    private function processAirtelPayment(
        string $phoneNumber,
        int $amount,
        string $reference,
        string $description,
        BuyerContactFee $fee
    ): array {
        try {
            $result = $this->airtelMoneyService->collect(
                $phoneNumber,
                $amount,
                $reference,
                $description,
                false // Use sandbox for now
            );

            // Update fee with provider transaction ID
            $fee->update([
                'provider_transaction_id' => $result['provider_transaction_id'],
                'checkout_session_id' => $reference,
            ]);

            return [
                'provider_transaction_id' => $result['provider_transaction_id'],
                'message' => 'Airtel Money payment request sent successfully',
            ];
        } catch (\Exception $e) {
            report($e);
            throw ValidationException::withMessages([
                'status' => ['Airtel Money payment failed: ' . $e->getMessage()],
            ]);
        }
    }

    /**
     * Handle MTN MoMo webhook
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
     * Handle Airtel Money webhook
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
     * Process webhook from mobile money providers
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

        // Find the fee by reference
        $fee = BuyerContactFee::query()
            ->where('reference', $reference)
            ->orWhere('provider_transaction_id', $transactionId)
            ->first();

        if (! $fee) {
            Log::warning('Mobile money webhook: Fee not found', [
                'provider' => $provider,
                'reference' => $reference,
                'transaction_id' => $transactionId,
            ]);

            return ['processed' => false, 'error' => 'Fee not found'];
        }

        // Normalize status
        $normalizedStatus = match ($provider) {
            self::PROVIDER_MTN => $this->mtnMomoService->normalizeStatus($status),
            self::PROVIDER_AIRTEL => $this->airtelMoneyService->normalizeStatus($status),
            default => 'unknown',
        };

        if (in_array($normalizedStatus, ['paid', 'completed', 'successful', 'success'], true)) {
            $fee->update([
                'status' => 'charged',
                'payment_status' => 'paid',
                'charged_at' => now(),
                'callback_received_at' => now(),
            ]);

            try {
                if (! empty($fee->billing_email)) {
                    Mail::to($fee->billing_email)->send(new SellerBillingPaymentMail(
                        'Contact Fee Payment Received',
                        'Contact fee paid successfully',
                        sprintf('You have successfully paid the contact fee for property #%d. You can now contact the seller.', $fee->property_id),
                        ['contact_fee_id' => $fee->id],
                        null,
                        null
                    ));
                }
            } catch (\Throwable $e) {
                report($e);
            }
        } elseif (in_array($normalizedStatus, ['failed', 'declined', 'invalid', 'expired', 'rejected'], true)) {
            $fee->update([
                'status' => 'failed',
                'payment_status' => 'failed',
                'callback_received_at' => now(),
            ]);
        }

        return ['processed' => true, 'fee_id' => $fee->id];
    }

    /**
     * Handle callback for mobile money (for non-webhook callbacks)
     */
    public function handleMobileMoneyCallback(array $payload): bool
    {
        $reference = strtolower((string) ($payload['merchant_reference'] ?? $payload['reference'] ?? ''));
        $orderTrackingId = (string) ($payload['order_tracking_id'] ?? $payload['transaction_id'] ?? '');

        if (! str_starts_with($reference, 'contact_')) {
            return false;
        }

        $fee = BuyerContactFee::query()
            ->where('reference', $reference)
            ->orWhere('provider_transaction_id', $orderTrackingId)
            ->first();

        if (! $fee) {
            return false;
        }

        $fee->update([
            'callback_received_at' => now(),
        ]);

        return true;
    }

    private function chargeContactFee(User $user, Property $property): BuyerContactFee
    {
        return BuyerContactFee::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'property_id' => $property->id,
            ],
            [
                'provider' => 'mock',
                'amount_ugx' => self::CONTACT_FEE_AMOUNT_UGX,
                'currency' => 'UGX',
                'status' => 'charged',
                'payment_status' => 'paid',
                'reference' => sprintf('mock_contact_%d_%d', $user->id, $property->id),
                'charged_at' => now(),
            ]
        );
    }
}
