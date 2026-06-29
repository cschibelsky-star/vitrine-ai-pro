<?php

use App\Http\Controllers\AiRunController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/admin/centro-ia/executar', [AiRunController::class, 'create'])->name('ai.run.create');
    Route::post('/admin/centro-ia/executar', [AiRunController::class, 'store'])->name('ai.run.store');
});
