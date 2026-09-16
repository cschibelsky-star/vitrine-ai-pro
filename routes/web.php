<?php

use App\Http\Controllers\ClientPortalController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

/*
|--------------------------------------------------------------------------
| Compatibilidade de autenticação
|--------------------------------------------------------------------------
| O middleware auth padrão do Laravel procura uma rota nomeada "login".
| Como o login real do projeto é o login do Filament em /admin/login,
| criamos este alias para evitar erro: Route [login] not defined.
*/
Route::get('/login', function () {
    return redirect('/admin/login');
})->name('login');

Route::get('/sso/cockpit', function (Request $request) {
    $token = (string) $request->query('token', '');
    abort_unless(strlen($token) === 64, 404);

    $response = Http::timeout(5)
        ->acceptJson()
        ->get('https://hml.vitrineiapro.com.br/cockpit/sso/consume', ['token' => $token]);

    abort_unless($response->successful(), 403);

    $payload = $response->json();
    abort_unless(is_array($payload) && ($payload['target'] ?? null) === 'factory', 403);

    $email = (string) ($payload['email'] ?? '');
    $issuedAt = (int) ($payload['issued_at'] ?? 0);
    abort_unless($email !== '' && $issuedAt > 0 && abs(now()->timestamp - $issuedAt) <= 90, 403);

    $user = User::where('email', $email)->first();
    abort_unless($user && ($user->is_active ?? true) && $user->isAdmin(), 403);

    Auth::guard('web')->login($user, false);
    $request->session()->regenerate();

    return redirect('/admin');
})->middleware('throttle:20,1')->name('cockpit.sso');

Route::middleware(['auth'])->group(function () {
    Route::get('/cliente', [ClientPortalController::class, 'index'])->name('client.portal');

    Route::post('/cliente/logout', function (Request $request) {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/admin/login');
    })->name('client.logout');
});


if (file_exists(__DIR__.'/client_portal_auth.php')) {
    require __DIR__.'/client_portal_auth.php';
}

if (file_exists(__DIR__.'/client_support_tickets.php')) {
    require __DIR__.'/client_support_tickets.php';
}

if (file_exists(__DIR__.'/ai_run.php')) {
    require __DIR__.'/ai_run.php';
}

if (file_exists(__DIR__.'/asaas.php')) {
    require __DIR__.'/asaas.php';
}

if (file_exists(__DIR__.'/ai_provider_test.php')) {
    require __DIR__.'/ai_provider_test.php';
}

if (file_exists(__DIR__.'/master_2_0.php')) {
    require __DIR__.'/master_2_0.php';
}

if (file_exists(__DIR__.'/heygen_callback.php')) {
    require __DIR__.'/heygen_callback.php';
}

if (file_exists(__DIR__.'/via_factory.php')) {
    require __DIR__.'/via_factory.php';
}
