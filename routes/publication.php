<?php

use App\Http\Controllers\Deploy\PublicationController;
use Illuminate\Support\Facades\Route;

Route::post('/admin/publication/approve', [PublicationController::class, 'approve'])
    ->middleware(['auth', 'throttle:10,1'])->name('publication.approve');
