<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\PublicController;
use App\Http\Controllers\Api\PropertyController;
use App\Http\Controllers\Api\ImageUploadController;
use App\Http\Controllers\Api\AuthProfileController;
use App\Http\Controllers\Api\SellerBillingController;
use App\Http\Controllers\Api\TenantDashboardController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Demo mode status - public endpoint for frontend to check
Route::get('demo-mode', function () {
    return response()->json(['demo_mode' => config('app.demo_mode')]);
});

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
});

Route::middleware('auth:sanctum')->prefix('seller/billing')->group(function () {
    Route::get('status', [SellerBillingController::class, 'status']);
    Route::post('subscribe', [SellerBillingController::class, 'subscribe']);
    Route::post('cancel', [SellerBillingController::class, 'cancel']);
});

Route::post('webhooks/pesapal', [SellerBillingController::class, 'pesapalWebhook']);
Route::post('callbacks/pesapal', [SellerBillingController::class, 'pesapalCallback']);
Route::get('callbacks/pesapal', [SellerBillingController::class, 'pesapalCallback']);
