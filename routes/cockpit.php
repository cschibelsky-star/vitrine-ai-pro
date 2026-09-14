<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->get('/cockpit', function () {
    $user = auth()->user();
    abort_unless($user && $user->isAdmin(), 403);

    $applications = collect(config('cockpit-applications', []))
        ->filter(fn (array $app) => in_array($user->role ?: 'admin', $app['roles'] ?? [], true))
        ->values();

    return view('cockpit.index', compact('applications'));
})->name('cockpit.index');
