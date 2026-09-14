<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

Route::get('/cockpit/login', function () {
    if (Auth::check()) {
        return redirect()->route('cockpit.index');
    }

    return view('cockpit.login');
})->name('cockpit.login');

Route::post('/cockpit/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required', 'string'],
    ]);

    if (! Auth::attempt($credentials, $request->boolean('remember'))) {
        throw ValidationException::withMessages([
            'email' => 'Credenciais invalidas.',
        ]);
    }

    $request->session()->regenerate();
    $user = Auth::user();

    if (! $user || ! $user->is_active || ! $user->isAdmin()) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        throw ValidationException::withMessages([
            'email' => 'Usuario sem permissao para acessar o Cockpit.',
        ]);
    }

    return redirect()->route('cockpit.index');
})->middleware('throttle:5,1')->name('cockpit.login.submit');

Route::get('/cockpit', function () {
    if (! Auth::check()) {
        return redirect()->route('cockpit.login');
    }

    $user = Auth::user();
    abort_unless($user && $user->is_active && $user->isAdmin(), 403);

    $applications = collect(config('cockpit-applications', []))
        ->filter(fn (array $app) => in_array($user->role ?: 'admin', $app['roles'] ?? [], true))
        ->values();

    return view('cockpit.index', compact('applications'));
})->name('cockpit.index');

Route::post('/cockpit/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('cockpit.login');
})->name('cockpit.logout');
