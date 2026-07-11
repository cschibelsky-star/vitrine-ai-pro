<?php

use App\Http\Controllers\Api\LeadCaptureController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:60,1')->group(function () {
    Route::post('/leads', [LeadCaptureController::class, 'store'])
        ->name('api.leads.store');
});

require __DIR__.'/site_factory_api.php';
