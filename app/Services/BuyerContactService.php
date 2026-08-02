<?php

namespace App\Services;

use App\Mail\SellerBillingPaymentMail;
use App\Models\BuyerContactFee;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class BuyerContactService
{
    private const PROVIDER_PESAPAL = 'pesapal';

    private const CONTACT_FEE_AMOUNT_UGX = 10000;
    private const CONTACT_FEE_AMOUNT_USD = 3;

    private const PESAPAL_AUTH_PATH = '/api/Auth/RequestToken';
    private const PESAPAL_SUBMIT_ORDER_PATH = '/api/Transactions/SubmitOrderRequest';
    private const PESAPAL_STATUS_PATH = '/api/Transactions/GetTransactionStatus';

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
            $fee = $this->syncPendingPesapalFee($fee);
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

        $amount = $this->effectiveContactFeeAmountUgx();
        $providerReference = sprintf('contact_%d_%d_%s', $user->id, $property->id, now()->format('YmdHis'));

        $fee = BuyerContactFee::query()->updateOrCreate(
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
                'billing_email' => $attributes['billing_email'] ?? $user->email,
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
            'description' => sprintf('Contact seller for property #%d', $property->id),
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
                'property_id' => (string) $property->id,
                'contact_fee_id' => (string) $fee->id,
                'provider' => self::PROVIDER_PESAPAL,
                'type' => 'buyer_contact_fee',
            ],
        ];

        $response = Http::withToken($token)
            ->acceptJson()
            ->post($baseUrl.self::PESAPAL_SUBMIT_ORDER_PATH, $payload);

        if ($response->failed()) {
            $errorMessage = (string) data_get($response->json(), 'error.message', 'Unable to create contact fee checkout session.');

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

        $fee->update([
            'provider_transaction_id' => $orderTrackingId,
            'checkout_session_id' => $merchantReference,
            'status' => 'failed',
        ]);

        return [
            'fee' => $fee->fresh(),
            'checkout' => [
                'url' => $redirectUrl,
                'payment_status' => 'pending',
                'order_tracking_id' => $orderTrackingId,
                'merchant_reference' => $merchantReference,
            ],
        ];
    }

    public function syncPendingPesapalFee(BuyerContactFee $fee): BuyerContactFee
    {
        if ($fee->status === 'charged' || $fee->payment_status === 'paid') {
            return $fee;
        }

        if ($fee->status !== 'failed' || $fee->payment_status !== 'pending' || $fee->provider !== self::PROVIDER_PESAPAL) {
            return $fee;
        }

        $baseUrl = rtrim((string) config('services.pesapal.base_url'), '/');
        $consumerKey = (string) config('services.pesapal.consumer_key');
        $consumerSecret = (string) config('services.pesapal.consumer_secret');

        if ($baseUrl === '' || $consumerKey === '' || $consumerSecret === '') {
            return $fee;
        }

        $orderTrackingId = $fee->provider_transaction_id;
        if ($orderTrackingId === null) {
            return $fee;
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
                } elseif (in_array($status, ['failed', 'declined', 'invalid', 'expired'], true)) {
                    $fee->update([
                        'status' => 'failed',
                        'payment_status' => 'failed',
                        'callback_received_at' => now(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            report($e);
        }

        return $fee;
    }

    public function handlePesapalWebhookPayload(string $payload): void
    {
        $data = json_decode($payload, true);
        if (! is_array($data)) {
            return;
        }

        $merchantReference = strtolower((string) data_get($data, 'MerchantReference', data_get($data, 'merchant_reference', '')));
        $status = strtolower((string) data_get($data, 'Status', data_get($data, 'status', '')));

        if (! str_starts_with($merchantReference, 'contact_')) {
            return;
        }

        $fee = BuyerContactFee::query()
            ->where('reference', $merchantReference)
            ->orWhere('provider_transaction_id', data_get($data, 'OrderTrackingId', data_get($data, 'order_tracking_id', '')))
            ->first();

        if (! $fee) {
            return;
        }

        if (in_array($status, ['completed', 'successful', 'success', 'paid'], true)) {
            $fee->update([
                'status' => 'charged',
                'payment_status' => 'paid',
                'charged_at' => now(),
                'callback_received_at' => now(),
            ]);
        } elseif (in_array($status, ['failed', 'declined', 'invalid', 'expired'], true)) {
            $fee->update([
                'status' => 'failed',
                'payment_status' => 'failed',
                'callback_received_at' => now(),
            ]);
        }
    }

    public function handlePesapalCallbackPayload(array $payload): bool
    {
        $merchantReference = strtolower((string) ($payload['merchant_reference'] ?? ''));
        $orderTrackingId = (string) ($payload['order_tracking_id'] ?? '');

        if (! str_starts_with($merchantReference, 'contact_')) {
            return false;
        }

        $fee = BuyerContactFee::query()
            ->where('reference', $merchantReference)
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
