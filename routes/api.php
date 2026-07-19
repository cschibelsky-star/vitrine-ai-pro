<?php

use App\Http\Controllers\Api\LeadCaptureController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:60,1')->group(function () {
    Route::post('/leads', [LeadCaptureController::class, 'store'])
        ->name('api.leads.store');
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
