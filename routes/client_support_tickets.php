<?php

use App\Http\Controllers\ClientSupportTicketController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::post('/cliente/chamados', [ClientSupportTicketController::class, 'store'])->name('client.support-tickets.store');
});
