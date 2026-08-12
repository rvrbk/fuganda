<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\PublicController;
use App\Http\Controllers\Api\PropertyController;
use App\Http\Controllers\Api\ImageUploadController;
use App\Http\Controllers\Api\AuthProfileController;
use App\Http\Controllers\Api\TenantDashboardController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Demo mode status - public endpoint for frontend to check
Route::get('demo-mode', function () {
    return response()->json(['demo_mode' => config('app.demo_mode')]);
});

// ============================================================================
// OAuth2 Token Endpoints (for external API clients with client_id/client_secret)
// ============================================================================

// OAuth2 Token Endpoint (handles both client_credentials and password grants)
Route::post('oauth/token', [\App\Http\Controllers\Api\OAuthTokenController::class, 'issueToken']);

// OAuth Token Management
Route::middleware('auth:sanctum')->group(function () {
    Route::post('oauth/revoke', [\App\Http\Controllers\Api\OAuthTokenController::class, 'revokeToken']);
    Route::get('oauth/token/info', [\App\Http\Controllers\Api\OAuthTokenController::class, 'tokenInfo']);
});

// API Client Management (Admin)
Route::middleware('auth:sanctum')->prefix('oauth/clients')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\ApiClientController::class, 'index']);
    Route::post('/', [\App\Http\Controllers\Api\ApiClientController::class, 'store']);
    Route::get('/{client}', [\App\Http\Controllers\Api\ApiClientController::class, 'show']);
    Route::delete('/{client}', [\App\Http\Controllers\Api\ApiClientController::class, 'destroy']);
    Route::post('/{client}/regenerate', [\App\Http\Controllers\Api\ApiClientController::class, 'regenerateSecret']);
});

// ============================================================================
// External API Authentication Routes (for third-party consumers)
// ============================================================================

Route::post('auth/login', [\App\Http\Controllers\Api\ApiAuthController::class, 'login']);

Route::get('properties', [PropertyController::class, 'index']);
Route::get('properties/{id}', [PropertyController::class, 'show'])->whereNumber('id');
Route::get('locations', [LocationController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('dashboard', [TenantDashboardController::class, 'show']);
    Route::post('uploads/images', [ImageUploadController::class, 'store']);
    Route::post('uploads/media', [ImageUploadController::class, 'storeMedia']);
    Route::post('properties', [PropertyController::class, 'store']);
    Route::put('properties/{property}', [PropertyController::class, 'update'])->whereNumber('property');
    Route::patch('properties/{property}', [PropertyController::class, 'update'])->whereNumber('property');
    Route::delete('properties/{property}', [PropertyController::class, 'destroy'])->whereNumber('property');
    Route::get('messages', [MessageController::class, 'index']);
    Route::get('messages/unread-count', [MessageController::class, 'unreadCount']);
    Route::post('messages', [MessageController::class, 'store']);
});

Route::prefix('public')->group(function () {
    Route::get('ping', [PublicController::class, 'ping']);
    Route::get('properties', [PropertyController::class, 'index']);
    Route::get('properties/{id}', [PropertyController::class, 'show'])->whereNumber('id');
    Route::post('property-contact', [PublicController::class, 'contactSeller'])->middleware('throttle:20,1');
});

Route::middleware('auth:sanctum')->prefix('auth')->group(function () {
    Route::get('me', [AuthProfileController::class, 'show']);
    // External API token management
    Route::get('me/detailed', [\App\Http\Controllers\Api\ApiAuthController::class, 'me']);
    Route::post('logout', [\App\Http\Controllers\Api\ApiAuthController::class, 'logout']);
    Route::post('tokens/revoke', [\App\Http\Controllers\Api\ApiAuthController::class, 'revokeToken']);
    Route::post('tokens/revoke-all', [\App\Http\Controllers\Api\ApiAuthController::class, 'revokeAllTokens']);
});

Route::middleware('auth:sanctum')->prefix('buyer/contact')->group(function () {
    Route::get('status/{property}', [\App\Http\Controllers\Api\BuyerContactController::class, 'status']);
    Route::post('checkout/{property}', [\App\Http\Controllers\Api\BuyerContactController::class, 'checkout']);
});

Route::middleware('auth:sanctum')->prefix('buyer/billing')->group(function () {
    Route::get('status', [\App\Http\Controllers\Api\BuyerBillingController::class, 'status']);
    Route::post('subscribe', [\App\Http\Controllers\Api\BuyerBillingController::class, 'subscribe']);
    Route::post('cancel', [\App\Http\Controllers\Api\BuyerBillingController::class, 'cancel']);
});

// Mobile Money Webhooks
Route::post('webhooks/mtn-momo', [\App\Http\Controllers\Api\BuyerContactController::class, 'mtnWebhook']);
Route::post('webhooks/airtel-money', [\App\Http\Controllers\Api\BuyerContactController::class, 'airtelWebhook']);
Route::post('callbacks/mtn-momo', [\App\Http\Controllers\Api\BuyerContactController::class, 'mobileMoneyCallback']);
Route::get('callbacks/mtn-momo', [\App\Http\Controllers\Api\BuyerContactController::class, 'mobileMoneyCallback']);
Route::post('callbacks/airtel-money', [\App\Http\Controllers\Api\BuyerContactController::class, 'mobileMoneyCallback']);
Route::get('callbacks/airtel-money', [\App\Http\Controllers\Api\BuyerContactController::class, 'mobileMoneyCallback']);

// Billing Webhooks
Route::post('webhooks/mtn-momo/billing', [\App\Http\Controllers\Api\BuyerBillingController::class, 'mtnWebhook']);
Route::post('webhooks/airtel-money/billing', [\App\Http\Controllers\Api\BuyerBillingController::class, 'airtelWebhook']);
Route::post('callbacks/mtn-momo/billing', [\App\Http\Controllers\Api\BuyerBillingController::class, 'mobileMoneyCallback']);
Route::get('callbacks/mtn-momo/billing', [\App\Http\Controllers\Api\BuyerBillingController::class, 'mobileMoneyCallback']);
Route::post('callbacks/airtel-money/billing', [\App\Http\Controllers\Api\BuyerBillingController::class, 'mobileMoneyCallback']);
Route::get('callbacks/airtel-money/billing', [\App\Http\Controllers\Api\BuyerBillingController::class, 'mobileMoneyCallback']);
