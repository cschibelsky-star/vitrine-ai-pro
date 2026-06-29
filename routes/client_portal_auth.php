<?php

use App\Http\Controllers\ClientAuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::get('/cliente/login', [ClientAuthController::class, 'showLogin'])->name('client.login');
    Route::post('/cliente/login', [ClientAuthController::class, 'login'])->name('client.login.submit');
    Route::post('/cliente/logout', [ClientAuthController::class, 'logout'])->name('client.logout');
    Route::get('/cliente/logout', [ClientAuthController::class, 'logout']);
});
