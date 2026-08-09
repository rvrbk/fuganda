<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BuyerContactService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class BuyerContactController extends Controller
{
    public function __construct(private readonly BuyerContactService $contactService)
    {
    }

    public function status(Request $request, int $propertyId): JsonResponse
    {
        $user = $request->user();
        $property = \App\Models\Property::findOrFail($propertyId);

        $hasPaid = $this->contactService->hasPaidForProperty($user, $property);

        return response()->json([
            'has_paid' => $hasPaid,
            'property_id' => $propertyId,
            'contact_fee_amount_ugx' => $this->contactService->effectiveContactFeeAmountUgx(),
        ]);
    }

    public function checkout(Request $request, int $propertyId): JsonResponse
    {
        $user = $request->user();
        $property = \App\Models\Property::findOrFail($propertyId);

        if ((int) $user->id === (int) $property->user_id) {
            return response()->json([
                'paid' => true,
                'message' => 'You own this property, no fee required.',
            ]);
        }

        $validated = $request->validate([
            'payment_provider' => ['sometimes', 'in:mtn,airtel,mobile_money'],
            'payment_method' => ['sometimes', 'in:mobile_money,card,mtn,airtel'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'billing_email' => ['nullable', 'email', 'max:255'],
            'success_url' => ['sometimes', 'url', 'max:2048'],
            'cancel_url' => ['sometimes', 'url', 'max:2048'],
        ]);

        // Normalize payment provider
        if (! isset($validated['payment_provider']) && isset($validated['payment_method'])) {
            $validated['payment_provider'] = $validated['payment_method'];
        }

        $result = $this->contactService->requestContactFeeCheckout($user, $property, $validated);

        return response()->json([
            'has_paid' => $result['fee']?->isPaid() ?? false,
            'fee' => $result['fee'],
            'checkout' => $result['checkout'],
            'checkout_url' => data_get($result, 'checkout.url'),
            'payment_status' => data_get($result, 'checkout.payment_status'),
            'provider' => data_get($result, 'checkout.provider', $validated['payment_provider'] ?? 'mtn'),
        ]);
    }

    public function mtnWebhook(Request $request): JsonResponse
    {
        $signatureHeader = (string) $request->header('X-MTN-Signature', '');
        $payload = $request->getContent();

        $result = $this->contactService->handleMtnWebhook($payload, $signatureHeader);

        return response()->json([
            'received' => true,
            'processed' => $result['processed'] ?? true,
        ]);
    }

    public function airtelWebhook(Request $request): JsonResponse
    {
        $signatureHeader = (string) $request->header('X-Airtel-Signature', '');
        $payload = $request->getContent();

        $result = $this->contactService->handleAirtelWebhook($payload, $signatureHeader);

        return response()->json([
            'received' => true,
            'processed' => $result['processed'] ?? true,
        ]);
    }

    public function mobileMoneyCallback(Request $request): JsonResponse
    {
        $merchantReference = $this->firstNonEmpty($request, [
            'merchant_reference',
            'MerchantReference',
            'order_merchant_reference',
            'OrderMerchantReference',
            'orderMerchantReference',
            'reference',
            'Reference',
        ]);

        $orderTrackingId = $this->firstNonEmpty($request, [
            'order_tracking_id',
            'OrderTrackingId',
            'orderTrackingId',
            'Order_Tracking_Id',
            'order_trackingid',
            'transaction_id',
            'TransactionId',
        ]);

        $processed = $this->contactService->handleMobileMoneyCallback(array_filter([
            'merchant_reference' => $merchantReference,
            'order_tracking_id' => $orderTrackingId,
        ]));

        if (! $processed) {
            Log::warning('Mobile money callback could not be processed.', [
                'query' => $request->query(),
                'merchant_reference' => $merchantReference,
                'order_tracking_id' => $orderTrackingId,
            ]);

            return response()->json([
                'received' => true,
                'processed' => false,
            ], 202);
        }

        return response()->json(['received' => true]);
    }

    private function firstNonEmpty(Request $request, array $keys): string
    {
        foreach ($keys as $key) {
            $value = (string) $request->query($key, $request->input($key, ''));
            if (trim($value) !== '') {
                return $value;
            }
        }

        return '';
    }
}
