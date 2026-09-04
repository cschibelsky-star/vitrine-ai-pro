<?php

declare(strict_types=1);

use App\Http\Controllers\Api\SiteFactoryIntakeController;
use Illuminate\Support\Facades\Route;

Route::post('/site/factory/intake', SiteFactoryIntakeController::class)
    ->name('site.factory.intake');

require __DIR__ . '/factory_generated.php';
