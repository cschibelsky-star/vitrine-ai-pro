<?php

use Illuminate\Support\Facades\Route;

Route::apiResource('clientes', \App\Http\Controllers\Api\ClienteApiController::class);
Route::apiResource('animais', \App\Http\Controllers\Api\AnimalApiController::class);
Route::apiResource('agendamentos', \App\Http\Controllers\Api\AgendamentoApiController::class);
Route::apiResource('prontuarios', \App\Http\Controllers\Api\ProntuarioApiController::class);
Route::apiResource('vacinas', \App\Http\Controllers\Api\VacinaApiController::class);
Route::apiResource('financeiro', \App\Http\Controllers\Api\FinanceiroApiController::class);
