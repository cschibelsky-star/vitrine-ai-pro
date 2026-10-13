<?php
use App\Http\Controllers\AssessorTrialController;
use Illuminate\Support\Facades\Route;
Route::middleware([\App\Http\Middleware\AssessorTrialGate::class,'auth','throttle:10,1'])->group(function () {
    Route::get('/assessor/avaliacao',[AssessorTrialController::class,'index'])->name('assessor.trial');
    Route::post('/assessor/avaliacao',[AssessorTrialController::class,'store']);
    Route::post('/assessor/avaliacao/{id}/cancelar',[AssessorTrialController::class,'cancel'])->where('id','[a-f0-9]{32}');
});
