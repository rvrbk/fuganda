<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SellerBillingService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class SellerBillingController extends Controller
{
    public function __construct(private readonly SellerBillingService $billingService)
    {
    }

    public function status(Request $request): JsonResponse
    {
        $this->authorizeSellerAccess($request);

        return response()->json($this->billingService->statusFor($request->user()));
    }

    public function subscribe(Request $request): JsonResponse
    {
        $this->authorizeSellerAccess($request);

        $validated = $request->validate([
            'plan_code' => ['sometimes', 'string', 'max:100'],
            'amount_ugx' => ['sometimes', 'integer', 'min:0'],
            'currency' => ['sometimes', 'in:UGX'],
            'payment_method' => ['required', 'in:mobile_money,card'],
            'billing_email' => ['required', 'email', 'max:255'],
            'success_url' => ['sometimes', 'url', 'max:2048'],
            'cancel_url' => ['sometimes', 'url', 'max:2048'],
        ]);

        $result = $this->billingService->createCheckoutSession($request->user(), $validated);
        $subscription = $result['subscription'];

        return response()->json([
            'seller_has_active_subscription' => $this->billingService->hasActiveSubscription($request->user()->fresh('sellerSubscription')),
            'seller_subscription_status' => $subscription->status,
            'subscription' => $subscription,
            'checkout' => $result['checkout'],
            'checkout_url' => data_get($result, 'checkout.url'),
            'payment_status' => data_get($result, 'checkout.payment_status'),
        ]);
    }

    public function pesapalWebhook(Request $request): JsonResponse
    {
        $signatureHeader = (string) $request->header('X-Pesapal-Signature', '');
        $payload = $request->getContent();

        if (! $this->billingService->verifyPesapalWebhookSignature($payload, $signatureHeader)) {
            throw new BadRequestHttpException('Invalid Pesapal signature.');
        }

        $this->billingService->handlePesapalWebhookPayload($payload);

        return response()->json(['received' => true]);
    }

    public function pesapalCallback(Request $request): JsonResponse|RedirectResponse
    {
        $merchantReference = $this->firstNonEmpty($request, [
            'merchant_reference',
            'MerchantReference',
            'order_merchant_reference',
            'OrderMerchantReference',
            'orderMerchantReference',
        ]);

        $orderTrackingId = $this->firstNonEmpty($request, [
            'order_tracking_id',
            'OrderTrackingId',
            'orderTrackingId',
            'Order_Tracking_Id',
            'order_trackingid',
        ]);

        $processed = $this->billingService->handlePesapalCallbackPayload(array_filter([
            'merchant_reference' => $merchantReference,
            'order_tracking_id' => $orderTrackingId,
        ]));

        if (! $processed) {
            Log::warning('Pesapal callback could not be processed.', [
                'query' => $request->query(),
                'merchant_reference' => $merchantReference,
                'order_tracking_id' => $orderTrackingId,
            ]);

            if ($request->isMethod('get')) {
                return redirect()->to($this->resolveCallbackRedirectPath($merchantReference, false));
            }

            return response()->json([
                'received' => true,
                'processed' => false,
            ], 202);
        }

        if ($request->isMethod('get')) {
            return redirect()->to($this->resolveCallbackRedirectPath($merchantReference, true));
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

    private function resolveCallbackRedirectPath(string $merchantReference, bool $processed): string
    {
        $reference = strtolower(trim($merchantReference));
        $isPublishFeeReference = str_starts_with($reference, 'pub_');

        if ($isPublishFeeReference) {
            return $processed
                ? '/?owned=1&created=1'
                : '/?owned=1&created=1&billing_result=pending';
        }

        return '/seller/onboarding?billing_result=pending';
    }

    public function cancel(Request $request): JsonResponse
    {
        $this->authorizeSellerAccess($request);

        $subscription = $this->billingService->cancel($request->user());

        return response()->json([
            'seller_has_active_subscription' => false,
            'seller_subscription_status' => $subscription->status,
            'subscription' => $subscription,
        ]);
    }

    private function authorizeSellerAccess(Request $request): void
    {
        $user = $request->user();

        if (! $user->isSeller() && ! $user->isAdmin()) {
            throw new AuthorizationException('Only sellers can manage billing.');
        }
    }
}
