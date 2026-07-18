<?php

use App\Http\Controllers\Api\FlowAiRouterController;
use App\Http\Controllers\Api\FlowEventCallbackController;
use App\Http\Controllers\Api\FlowFeatureFlagController;
use App\Http\Controllers\Api\FlowGovernanceController;
use App\Http\Controllers\Api\FlowLockController;
use App\Http\Controllers\Api\FlowObservabilityController;
use App\Http\Controllers\Api\FlowPlatformServicesController;
use App\Http\Controllers\Api\FlowRuntimeController;
use App\Http\Controllers\Api\FlowSchedulerController;
use App\Http\Controllers\Api\FlowUsageController;
use App\Http\Controllers\Api\FlowWorkflowRegistryController;
use App\Http\Controllers\Api\LeadCaptureController;
use App\Http\Controllers\Api\MissionControlController;
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

    Route::post('/runtime/start', [FlowRuntimeController::class, 'start'])
        ->middleware('throttle:240,1')
        ->name('flow.runtime.start');

    Route::get('/runtime/executions/{uuid}', [FlowRuntimeController::class, 'show'])
        ->middleware('throttle:600,1')
        ->whereUuid('uuid')
        ->name('flow.runtime.executions.show');

    Route::post('/scheduler/upsert', [FlowSchedulerController::class, 'upsert'])
        ->middleware('throttle:120,1')
        ->name('flow.scheduler.upsert');

    Route::get('/scheduler/due', [FlowSchedulerController::class, 'due'])
        ->middleware('throttle:600,1')
        ->name('flow.scheduler.due');

    Route::post('/scheduler/dispatch-due', [FlowSchedulerController::class, 'dispatchDue'])
        ->middleware('throttle:120,1')
        ->name('flow.scheduler.dispatch-due');

    Route::post('/ai/route', [FlowAiRouterController::class, 'route'])
        ->middleware('throttle:240,1')
        ->name('flow.ai.route');

    Route::post('/secrets/upsert', [FlowPlatformServicesController::class, 'putSecret'])
        ->middleware('throttle:120,1')
        ->name('flow.secrets.upsert');

    Route::post('/secrets/resolve', [FlowPlatformServicesController::class, 'resolveSecret'])
        ->middleware('throttle:240,1')
        ->name('flow.secrets.resolve');

    Route::post('/storage/objects', [FlowPlatformServicesController::class, 'putStorage'])
        ->middleware('throttle:120,1')
        ->name('flow.storage.objects.put');

    Route::get('/storage/objects/{uuid}', [FlowPlatformServicesController::class, 'showStorage'])
        ->middleware('throttle:600,1')
        ->whereUuid('uuid')
        ->name('flow.storage.objects.show');

    Route::delete('/storage/objects/{uuid}', [FlowPlatformServicesController::class, 'deleteStorage'])
        ->middleware('throttle:120,1')
        ->whereUuid('uuid')
        ->name('flow.storage.objects.delete');

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

    Route::post('/audit', [FlowGovernanceController::class, 'audit'])
        ->middleware('throttle:600,1')
        ->name('flow.audit.store');

    Route::post('/compliance/requests', [FlowGovernanceController::class, 'createComplianceRequest'])
        ->middleware('throttle:120,1')
        ->name('flow.compliance.requests.create');

    Route::get('/compliance/requests/{uuid}', [FlowGovernanceController::class, 'showComplianceRequest'])
        ->middleware('throttle:240,1')
        ->whereUuid('uuid')
        ->name('flow.compliance.requests.show');

    Route::patch('/compliance/requests/{uuid}', [FlowGovernanceController::class, 'updateComplianceRequest'])
        ->middleware('throttle:120,1')
        ->whereUuid('uuid')
        ->name('flow.compliance.requests.update');
});

Route::prefix('mission')->middleware('throttle:600,1')->group(function () {
    Route::get('/overview', [MissionControlController::class, 'overview'])->name('mission.overview');
    Route::get('/executions', [MissionControlController::class, 'executions'])->name('mission.executions');
    Route::get('/workflows', [MissionControlController::class, 'workflows'])->name('mission.workflows');
    Route::get('/queues', [MissionControlController::class, 'queues'])->name('mission.queues');
    Route::get('/costs', [MissionControlController::class, 'costs'])->name('mission.costs');
    Route::get('/health', [MissionControlController::class, 'health'])->name('mission.health');
    Route::get('/dlq', [MissionControlController::class, 'dlq'])->name('mission.dlq');
});

require __DIR__.'/site_factory_api.php';

$factoryRouteFiles = glob(__DIR__.'/api_factory_*.php') ?: [];

if ($factoryRouteFiles !== []) {
    Route::middleware(config('factory_operational.generated_api_middleware', ['auth:sanctum']))
        ->group(function () use ($factoryRouteFiles): void {
            foreach ($factoryRouteFiles as $factoryRouteFile) {
                require $factoryRouteFile;
            }
        });
}
