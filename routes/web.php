<?php

use App\Http\Controllers\Auth\SocialAuthController;
use App\Models\Property;
use Illuminate\Support\Facades\Route;

Route::get('/sitemap.xml', function () {
    $properties = Property::published()
        ->select('id', 'updated_at')
        ->orderByDesc('updated_at')
        ->limit(50000)
        ->get();

    $content = view('sitemap', ['properties' => $properties])->render();

    return response($content, 200)
        ->header('Content-Type', 'application/xml');
})->name('sitemap');

Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])
    ->whereIn('provider', ['google', 'apple'])
    ->name('auth.social.redirect');

Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])
    ->whereIn('provider', ['google', 'apple'])
    ->name('auth.social.callback');

Route::get('/reset-password/{token}', function () {
    return view('welcome');
})->middleware('guest')->name('password.reset');

Route::get('/{any?}', function () {
    return view('welcome');
})->where('any', '.*')->name('spa.fallback');
