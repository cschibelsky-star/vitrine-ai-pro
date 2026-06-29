<?php
declare(strict_types=1);
use Illuminate\Support\Facades\Route;
Route::middleware(config('factory.middleware', ['web','auth']))->prefix(config('factory.route_prefix','factory'))->name('factory.')->group(function (): void {
    Route::get('/health', fn () => response()->json(['module'=>config('factory.name'),'version'=>config('factory.version'),'enabled'=>config('factory.enabled'),'status'=>'ok']))->name('health');
});
