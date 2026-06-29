<?php

use Illuminate\Support\Facades\Route;

Route::apiResource('registros', \App\Http\Controllers\Api\RegistroApiController::class);
Route::apiResource('categorias', \App\Http\Controllers\Api\CategoriaApiController::class);
Route::apiResource('documentos', \App\Http\Controllers\Api\DocumentoApiController::class);
