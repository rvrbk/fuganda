<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\PublicController;
use App\Http\Controllers\Api\PropertyController;
use App\Http\Controllers\Api\AuthProfileController;
use App\Http\Controllers\Api\TenantDashboardController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('properties', [PropertyController::class, 'index']);
Route::get('properties/{id}', [PropertyController::class, 'show'])->whereNumber('id');
Route::get('locations', [LocationController::class, 'index']);

Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::post('properties', [PropertyController::class, 'store']);
    Route::put('properties/{property}', [PropertyController::class, 'update'])->whereNumber('property');
    Route::patch('properties/{property}', [PropertyController::class, 'update'])->whereNumber('property');
    Route::delete('properties/{property}', [PropertyController::class, 'destroy'])->whereNumber('property');

    Route::get('messages', [MessageController::class, 'index']);
    Route::post('messages', [MessageController::class, 'store']);
});

Route::prefix('public')->group(function () {
    Route::get('ping', [PublicController::class, 'ping']);
    Route::get('tenant', [PublicController::class, 'tenantByDomain']);
    Route::get('properties', [PropertyController::class, 'index']);
    Route::get('properties/{id}', [PropertyController::class, 'show'])->whereNumber('id');
});

Route::middleware('auth:sanctum')->prefix('auth')->group(function () {
    Route::get('me', [AuthProfileController::class, 'show']);
});

Route::middleware(['auth:sanctum', 'tenant'])->prefix('tenant')->group(function () {
    Route::get('dashboard', [TenantDashboardController::class, 'show']);
    Route::get('locations', [LocationController::class, 'index']);
    Route::post('properties', [PropertyController::class, 'store']);
    Route::put('properties/{property}', [PropertyController::class, 'update'])->whereNumber('property');
    Route::patch('properties/{property}', [PropertyController::class, 'update'])->whereNumber('property');
    Route::delete('properties/{property}', [PropertyController::class, 'destroy'])->whereNumber('property');
    Route::get('messages', [MessageController::class, 'index']);
    Route::post('messages', [MessageController::class, 'store']);
});
