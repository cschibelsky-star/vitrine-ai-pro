<?php

use App\Http\Controllers\Api\FlowEventCallbackController;
use App\Http\Controllers\Api\LeadCaptureController;
use App\Http\Controllers\Api\VitrineFlowProvisionController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:60,1')->group(function () {
    Route::post('/leads', [LeadCaptureController::class, 'store']);
});

Route::prefix('vitrine-flow')->group(function () {
    Route::post('/provision/dispatch', [VitrineFlowProvisionController::class, 'dispatch'])
        ->middleware('auth:sanctum')
        ->name('vitrine-flow.provision.dispatch');

    Route::post('/provision/callback', [VitrineFlowProvisionController::class, 'callback'])
        ->middleware('throttle:120,1')
        ->name('vitrine-flow.provision.callback');
});

Route::prefix('flow')->group(function () {
    Route::post('/events/callback', FlowEventCallbackController::class)
        ->middleware('throttle:240,1')
        ->name('flow.events.callback');
});

require __DIR__.'/site_factory_api.php';
