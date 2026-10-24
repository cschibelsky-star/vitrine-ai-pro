<?php
use App\Http\Controllers\AssessorCabinetController;
use App\Http\Controllers\AssessorDashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('assessor/gabinetes')->group(function () {
    Route::get('/{cabinet}/painel', [AssessorDashboardController::class, 'index'])->whereNumber('cabinet');
    Route::post('/', [AssessorCabinetController::class, 'createCabinet']);
    Route::post('/{cabinet}/assessores', [AssessorCabinetController::class, 'addMember'])->whereNumber('cabinet');
    Route::get('/{cabinet}/demandas', [AssessorCabinetController::class, 'index'])->whereNumber('cabinet');
    Route::post('/{cabinet}/demandas', [AssessorCabinetController::class, 'createDemand'])->whereNumber('cabinet');
    Route::post('/{cabinet}/demandas/{demand}/atribuir', [AssessorCabinetController::class, 'assign'])->whereNumber('cabinet')->whereNumber('demand');
    Route::patch('/{cabinet}/demandas/{demand}/status', [AssessorCabinetController::class, 'updateStatus'])->whereNumber('cabinet')->whereNumber('demand');
});
