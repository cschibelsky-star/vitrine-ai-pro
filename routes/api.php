<?php

use App\Http\Controllers\Api\CentroIaBrokerController;
use App\Http\Controllers\Api\LeadCaptureController;
use App\Http\Controllers\Api\SocialCheckoutController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:60,1')->group(function () {
    Route::post('/leads', [LeadCaptureController::class, 'store'])
        ->name('api.leads.store');
});

Route::middleware('throttle:30,1')->group(function () {
    Route::post('/internal/centro-ia/execute', [CentroIaBrokerController::class, 'execute'])
        ->name('api.internal.centro-ia.execute');
});

Route::middleware('throttle:30,1')->group(function () {
    Route::post('/internal/social/checkout/order', [SocialCheckoutController::class, 'order'])
        ->name('api.internal.social.checkout.order');

    Route::post('/internal/social/checkout/paid', [SocialCheckoutController::class, 'paid'])
        ->name('api.internal.social.checkout.paid');
});

require __DIR__.'/site_factory_api.php';
