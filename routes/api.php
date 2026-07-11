<?php

use App\Http\Controllers\Api\FlowEventCallbackController;
use App\Http\Controllers\Api\FlowFeatureFlagController;
use App\Http\Controllers\Api\FlowLockController;
use App\Http\Controllers\Api\FlowObservabilityController;
use App\Http\Controllers\Api\FlowUsageController;
use App\Http\Controllers\Api\FlowWorkflowRegistryController;
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

    Route::post('/workflows/register', [FlowWorkflowRegistryController::class, 'register'])
        ->middleware('throttle:120,1')
        ->name('flow.workflows.register');

    Route::get('/workflows/{uuid}', [FlowWorkflowRegistryController::class, 'resolve'])
        ->middleware('throttle:240,1')
        ->whereUuid('uuid')
        ->name('flow.workflows.resolve');

    Route::post('/locks/acquire', [FlowLockController::class, 'acquire'])
        ->middleware('throttle:240,1')
        ->name('flow.locks.acquire');

    Route::post('/locks/release', [FlowLockController::class, 'release'])
        ->middleware('throttle:240,1')
        ->name('flow.locks.release');

    Route::post('/quota/check', [FlowUsageController::class, 'check'])
        ->middleware('throttle:240,1')
        ->name('flow.quota.check');

    Route::post('/usage/reserve', [FlowUsageController::class, 'reserve'])
        ->middleware('throttle:240,1')
        ->name('flow.usage.reserve');

    Route::post('/usage/commit', [FlowUsageController::class, 'commit'])
        ->middleware('throttle:240,1')
        ->name('flow.usage.commit');

    Route::post('/telemetry', [FlowObservabilityController::class, 'telemetry'])
        ->middleware('throttle:600,1')
        ->name('flow.telemetry.store');

    Route::post('/dlq', [FlowObservabilityController::class, 'dlq'])
        ->middleware('throttle:240,1')
        ->name('flow.dlq.store');

    Route::post('/feature-flags/upsert', [FlowFeatureFlagController::class, 'upsert'])
        ->middleware('throttle:120,1')
        ->name('flow.feature-flags.upsert');

    Route::post('/feature-flags/check', [FlowFeatureFlagController::class, 'check'])
        ->middleware('throttle:600,1')
        ->name('flow.feature-flags.check');
});

require __DIR__.'/site_factory_api.php';
