<?php

use App\Http\Controllers\AiProviderTestController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/admin/centro-ia/provedores/{provider}/testar', AiProviderTestController::class)
        ->name('ai.providers.test');
});
