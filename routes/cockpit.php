<?php

use App\Http\Controllers\Cockpit\WebmailController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
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

Route::get('/cockpit/open/{slug}', function (string $slug) {
    abort_unless(Auth::check(), 403);

    $user = Auth::user();
    abort_unless($user && ($user->is_active ?? true) && $user->isAdmin(), 403);

    $app = collect(config('cockpit-applications', []))->firstWhere('slug', $slug);
    abort_unless($app, 404);

    $ssoUrl = $app['sso_url'] ?? ($slug === 'factory' ? 'https://factory.hml.vitrineiapro.com.br/sso/cockpit' : null);
    abort_unless($ssoUrl, 404);

    $token = Str::random(64);
    Cache::put('cockpit_sso_ticket:'.hash('sha256', $token), [
        'email' => $user->email,
        'role' => $user->role ?: 'admin',
        'target' => $app['sso_target'] ?? $slug,
        'issued_at' => now()->timestamp,
    ], now()->addSeconds(60));

    return redirect()->away($ssoUrl.'?token='.urlencode($token));
})->middleware('throttle:10,1')->name('cockpit.open');

Route::get('/cockpit/sso/consume', function (Request $request) {
    $token = (string) $request->query('token', '');
    abort_unless(strlen($token) === 64, 404);

    $payload = Cache::pull('cockpit_sso_ticket:'.hash('sha256', $token));
    abort_unless(is_array($payload), 404);

    return response()->json($payload);
})->middleware('throttle:30,1')->name('cockpit.sso.consume');

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

Route::get('/cockpit/webmail', [WebmailController::class, 'index'])->name('cockpit.webmail');
Route::get('/cockpit/webmail/message/{uid}', [WebmailController::class, 'show'])
    ->whereNumber('uid')
    ->name('cockpit.webmail.message');
Route::post('/cockpit/webmail/send', [WebmailController::class, 'send'])
    ->middleware('throttle:20,1')
    ->name('cockpit.webmail.send');
Route::get('/cockpit/webmail/probe', [WebmailController::class, 'probe'])
    ->middleware('throttle:10,1')
    ->name('cockpit.webmail.probe');

Route::post('/cockpit/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('cockpit.login');
})->name('cockpit.logout');