<?php

use App\Http\Controllers\HeygenTestController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/admin/centro-ia/heygen/testar', HeygenTestController::class)
        ->name('heygen.test');
});
