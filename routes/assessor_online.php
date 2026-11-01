<?php
declare(strict_types=1);

use App\Http\Controllers\AssessorOnline\DemandController;
use App\Http\Controllers\AssessorOnline\AssessorAuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->prefix('assessor')->name('assessor.')->group(function () {
    Route::get('/login', [AssessorAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AssessorAuthController::class, 'login'])->middleware('throttle:5,1')->name('login.submit');
    Route::post('/logout', [AssessorAuthController::class, 'logout'])->middleware('auth')->name('logout');
    Route::get('/esqueci-senha', [\App\Http\Controllers\AssessorOnline\AssessorPasswordController::class, 'requestForm'])->name('password.request');
    Route::post('/esqueci-senha', [\App\Http\Controllers\AssessorOnline\AssessorPasswordController::class, 'sendLink'])->middleware('throttle:3,1')->name('password.email');
    Route::get('/redefinir-senha/{token}', [\App\Http\Controllers\AssessorOnline\AssessorPasswordController::class, 'resetForm'])->name('password.reset');
    Route::post('/redefinir-senha', [\App\Http\Controllers\AssessorOnline\AssessorPasswordController::class, 'reset'])->middleware('throttle:5,1')->name('password.update');
});

Route::middleware(['web', 'auth'])->prefix('assessor/demandas')->name('assessor.demands.')->group(function () {
    Route::get('/', [DemandController::class, 'index'])->name('index');
    Route::get('/{caseId}', [DemandController::class, 'show'])->whereNumber('caseId')->name('show');
    Route::post('/{caseId}/situacao', [DemandController::class, 'transition'])->whereNumber('caseId')->name('transition');
});

Route::middleware(['web', 'auth'])->prefix('assessor/cidadaos')->name('assessor.citizens.')->group(function () {
    Route::get('/', [\App\Http\Controllers\AssessorOnline\CitizenController::class, 'index'])->name('index');
    Route::get('/novo', [\App\Http\Controllers\AssessorOnline\CitizenController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\AssessorOnline\CitizenController::class, 'store'])->name('store');
    Route::get('/{citizenId}', [\App\Http\Controllers\AssessorOnline\CitizenController::class, 'show'])->whereNumber('citizenId')->name('show');
    Route::post('/{citizenId}/demandas', [\App\Http\Controllers\AssessorOnline\CitizenController::class, 'registerDemand'])->whereNumber('citizenId')->name('demand');
});

Route::middleware(['web', 'auth'])->get('/assessor/painel', [\App\Http\Controllers\AssessorOnline\DashboardController::class, 'index'])->name('assessor.dashboard');
