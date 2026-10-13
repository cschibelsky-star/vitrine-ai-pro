<?php
use App\Assessor\HmlTrialProvider;
use App\Assessor\UnavailableHmlTrialProvider;
use App\Http\Controllers\AssessorTrialController;
use Illuminate\Support\Facades\Route;
// An official adapter must be explicitly bound; no HTTP endpoint is guessed.
if (!app()->bound(HmlTrialProvider::class)) { app()->bind(HmlTrialProvider::class,UnavailableHmlTrialProvider::class); }
Route::middleware(['auth','throttle:10,1',\App\Http\Middleware\AssessorTrialGate::class])->group(function () {
    Route::get('/assessor/avaliacao',[AssessorTrialController::class,'index'])->name('assessor.trial');
    Route::post('/assessor/avaliacao',[AssessorTrialController::class,'store']);
    Route::post('/assessor/avaliacao/{id}/cancelar',[AssessorTrialController::class,'cancel'])->where('id','[a-f0-9]{32}');
});
