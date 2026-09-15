<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

Route::get('/cockpit/login', function () {
    if (Auth::check()) {
        return redirect()->route('cockpit.index');
    }

    return view('cockpit.login', ['recoveryMode' => false]);
})->name('cockpit.login');

Route::get('/cockpit/forgot-password', function () {
    if (Auth::check()) {
        return redirect()->route('cockpit.index');
    }

    return view('cockpit.login', ['recoveryMode' => true]);
})->name('cockpit.password.request');

Route::post('/cockpit/forgot-password', function (Request $request) {
    $request->validate([
        'email' => ['required', 'email'],
    ]);

    return back()->with('status', 'Solicitacao de recuperacao registrada. O envio automatico por e-mail sera habilitado assim que o SMTP seguro do Cockpit estiver configurado.');
})->middleware('throttle:3,5')->name('cockpit.password.email');

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

    if (! $user || ! ($user->is_active ?? true) || ! $user->isAdmin()) {
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
    abort_unless($user && ($user->is_active ?? true) && $user->isAdmin(), 403);

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
