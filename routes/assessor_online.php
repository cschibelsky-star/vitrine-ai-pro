<?php
declare(strict_types=1);

use App\Http\Controllers\AssessorOnline\DemandController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('assessor/demandas')->name('assessor.demands.')->group(function () {
    Route::get('/', [DemandController::class, 'index'])->name('index');
    Route::get('/{caseId}', [DemandController::class, 'show'])->whereNumber('caseId')->name('show');
    Route::post('/{caseId}/situacao', [DemandController::class, 'transition'])->whereNumber('caseId')->name('transition');
});
