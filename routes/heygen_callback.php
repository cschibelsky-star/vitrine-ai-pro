<?php

use App\Http\Controllers\Api\HeygenCallbackController;
use Illuminate\Support\Facades\Route;

Route::post('/heygen/callback', [HeygenCallbackController::class, 'handle'])->name('heygen.callback');
Route::post('/heygen/webhook', [HeygenCallbackController::class, 'handleSigned'])->name('heygen.webhook');
Route::get('/heygen/callback', fn () => response()->json(['ok' => true, 'service' => 'heygen-callback']));
Route::get('/heygen/webhook', fn () => response()->json(['ok' => true, 'service' => 'heygen-webhook']));
