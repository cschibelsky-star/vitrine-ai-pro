<?php

use App\Http\Controllers\Cockpit\WebmailController;
use App\Services\CockpitWebmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
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

Route::post('/cockpit/internal/social-mail', function (Request $request, CockpitWebmailService $webmail) {
    $data = $request->validate([
        'project_id' => ['required', 'string', 'in:vitrine-ai-social-enterprise'],
        'timestamp' => ['required', 'integer'],
        'nonce' => ['required', 'string', 'min:32', 'max:80'],
        'to' => ['required', 'email', 'max:190'],
        'subject' => ['required', 'string', 'max:180'],
        'body' => ['required', 'string', 'max:20000'],
        'signature' => ['required', 'string', 'max:256'],
    ]);

    abort_if(abs(now()->timestamp - (int) $data['timestamp']) > 300, 401, 'expired_signature');

    $identity = Http::acceptJson()
        ->timeout(5)
        ->get('https://social.hml.vitrineiapro.com.br/service-identity/public-key')
        ->throw()
        ->json();

    abort_unless(($identity['project_id'] ?? null) === $data['project_id'], 401, 'invalid_identity');

    $publicKey = base64_decode((string) ($identity['public_key'] ?? ''), true);
    $signature = base64_decode($data['signature'], true);
    abort_unless(is_string($publicKey) && is_string($signature), 401, 'invalid_signature');

    $canonical = implode("\n", [
        $data['project_id'],
        (string) $data['timestamp'],
        $data['nonce'],
        mb_strtolower($data['to']),
        $data['subject'],
        hash('sha256', $data['body']),
    ]);

    abort_unless(sodium_crypto_sign_verify_detached($signature, $canonical, $publicKey), 401, 'invalid_signature');
    abort_unless(Cache::add('social_mail_nonce:'.hash('sha256', $data['nonce']), true, now()->addMinutes(10)), 409, 'replayed_request');

    $webmail->send('vendas', $data['to'], $data['subject'], $data['body']);

    return response()->json(['ok' => true], 202);
})->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class])
    ->middleware('throttle:30,1')
    ->name('cockpit.internal.social-mail');

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