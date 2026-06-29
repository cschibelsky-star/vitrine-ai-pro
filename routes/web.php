<?php

use App\Http\Controllers\ClientPortalController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
