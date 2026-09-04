<?php

declare(strict_types=1);

use App\Http\Controllers\ViaFactoryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'throttle:120,1'])
    ->prefix('via-factory')
    ->name('via.factory.')
    ->group(function (): void {
        Route::get('/context', [ViaFactoryController::class, 'context'])->name('context');
        Route::post('/chat', [ViaFactoryController::class, 'chat'])->name('chat');
        Route::post('/transcribe', [ViaFactoryController::class, 'transcribe'])->name('transcribe');
        Route::post('/action', [ViaFactoryController::class, 'action'])->name('action');
    });
