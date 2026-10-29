<?php
declare(strict_types=1);

use App\Http\Controllers\AssessorOnline\DemandController;
use Illuminate\Support\Facades\Route;

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
