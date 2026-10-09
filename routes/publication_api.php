<?php

use App\Http\Controllers\Deploy\PublicationController;
use Illuminate\Support\Facades\Route;

Route::post('/publication/consume', [PublicationController::class, 'consume'])->middleware('throttle:10,1');

Route::post('/publication/github-push', [PublicationController::class, 'push'])->middleware('throttle:60,1');
