<?php

use App\Http\Controllers\Api\FlowEventCallbackController;
use App\Http\Controllers\Api\FlowLockController;
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
});

require __DIR__.'/site_factory_api.php';
