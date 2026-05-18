<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->loadMissing('sellerSubscription');
        $subscription = $user->sellerSubscription;

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'corporation_id' => $user->corporation_id,
            'seller_has_active_subscription' => $user->hasActiveSellerSubscription(),
            'seller_subscription_status' => $user->sellerSubscriptionStatus(),
            'seller_subscription_plan_code' => $subscription?->plan_code,
            'seller_subscription_amount' => $subscription?->amount_ugx,
            'seller_subscription_currency' => $subscription?->currency,
            'unread_messages' => Message::query()
                ->where('receiver_id', $user->id)
                ->whereNull('read_at')
                ->count(),
        ]);
    }
}
