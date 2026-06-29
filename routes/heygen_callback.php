<?php

use App\Http\Controllers\Api\HeygenCallbackController;
use Illuminate\Support\Facades\Route;

Route::post('/api/heygen/callback', [HeygenCallbackController::class, 'handle'])->name('heygen.callback');
Route::get('/api/heygen/callback', fn () => response()->json(['ok' => true, 'service' => 'heygen-callback']));
