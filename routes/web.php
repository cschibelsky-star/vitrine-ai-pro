<?php

use App\Http\Controllers\ClientPortalController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/manifest.webmanifest', function () {
    return response()->json([
        'name' => 'Vitrine IA Pro',
        'short_name' => 'Vitrine IA Pro',
        'description' => 'Cockpit operacional do ecossistema Vitrine IA Pro',
        'start_url' => '/cockpit',
        'scope' => '/',
        'display' => 'standalone',
        'background_color' => '#0B1020',
        'theme_color' => '#0B1020',
        'lang' => 'pt-BR',
        'icons' => [
            ['src' => '/pwa/icon.svg', 'sizes' => 'any', 'type' => 'image/svg+xml', 'purpose' => 'any maskable'],
        ],
    ])->header('Content-Type', 'application/manifest+json');
});

Route::get('/pwa/icon.svg', function () {
    return response(<<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
  <rect width="512" height="512" rx="112" fill="#0B1020"/>
  <circle cx="256" cy="256" r="164" fill="none" stroke="#2563EB" stroke-width="30"/>
  <path d="M162 180h188l-94 190z" fill="#F8FAFC"/>
  <circle cx="256" cy="232" r="42" fill="#2563EB"/>
</svg>
SVG, 200, ['Content-Type' => 'image/svg+xml', 'Cache-Control' => 'public, max-age=86400']);
})->name('pwa.icon');

Route::get('/sw.js', function () {
    return response(<<<'JS'
const CACHE = "vitrine-ia-pro-shell-v1";
self.addEventListener("install", () => self.skipWaiting());
self.addEventListener("activate", event => event.waitUntil(self.clients.claim()));
self.addEventListener("fetch", event => {
    if (event.request.method !== "GET" || event.request.mode === "navigate") return;
    event.respondWith(fetch(event.request).catch(() => caches.match(event.request)));
});
JS, 200, ['Content-Type' => 'application/javascript', 'Service-Worker-Allowed' => '/']);
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

if (file_exists(__DIR__.'/cockpit.php')) {
    require __DIR__.'/cockpit.php';
}

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