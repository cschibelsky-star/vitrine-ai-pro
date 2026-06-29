<?php

use App\Http\Controllers\Api\AsaasWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/api/asaas/webhook', [AsaasWebhookController::class, 'handle'])->name('asaas.webhook');
Route::get('/api/asaas/webhook', fn () => response()->json(['ok' => true, 'service' => 'asaas-webhook']));
