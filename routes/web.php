<?php

use App\Http\Controllers\ClientPortalController;
use App\Http\Controllers\Marketing\VideoPreviewController;
use App\Marketing\Application\SocialDistributionHandoff;
use App\Marketing\Application\VideoFinalizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

Route::get('/robots.txt', function () {
    return response("User-agent: *\nAllow: /marketing/media/image/\n", 200, [
        'Content-Type' => 'text/plain; charset=UTF-8',
        'Cache-Control' => 'public, max-age=3600',
    ]);
});

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/login', function () {
    return redirect('/admin/login');
})->name('login');

Route::get('/marketing/media/reel-01/{version}', [VideoPreviewController::class, 'publicMedia'])
    ->middleware(['throttle:60,1'])
    ->where('version', '[A-Za-z0-9._-]+\\.mp4')
    ->name('marketing.media.reel-01');

Route::get('/marketing/media/reel-03/{version}', [VideoPreviewController::class, 'publicMediaReel03'])
    ->middleware(['throttle:30,1'])
    ->where('version', '[A-Za-z0-9._-]+\\.mp4')
    ->name('marketing.media.reel-03');

Route::get('/marketing/media/image/{filename}', [VideoPreviewController::class, 'publicImage'])
    ->middleware(['throttle:60,1'])
    ->where('filename', '[A-Za-z0-9._-]+\\.png')
    ->name('marketing.media.image');

Route::get('/marketing/video-preview/{version}', VideoPreviewController::class)
    ->middleware(['signed', 'throttle:30,1'])
    ->where('version', '[A-Za-z0-9._-]+')
    ->name('marketing.video-preview');

Route::post('/marketing/internal/publish-tv-sumare-article', function (Request $request, SocialDistributionHandoff $publisher) {
    $expected = (string) env('MARKETING_ENGINE_TOKEN', '');
    abort_unless($expected !== '' && hash_equals($expected, (string) $request->header('X-Marketing-Engine-Token', '')), 403);

    $validated = $request->validate([
        'url' => ['required', 'url', 'max:2048'],
    ]);

    return response()->json($publisher->publishTvSumareArticleNow((string) $validated['url']));
})->middleware(['throttle:10,1'])->name('marketing.internal.publish-tv-sumare-article');

Route::post('/marketing/internal/finalize-reel-03', function (Request $request, VideoFinalizationService $service) {
    $expected = (string) env('VIDEO_FINALIZE_TOKEN', '');
    abort_unless($expected !== '' && hash_equals($expected, (string) $request->header('X-Vitrine-Finalize-Token', '')), 403);

    $source = (string) env('VIDEO_FINALIZE_SOURCE_URL', '');
    abort_unless($source !== '', 503, 'video_source_not_configured');

    return response()->json($service->finalizeFromUrl(
        'REEL-03-VITRINE-SOCIAL-MIDIA-20260915',
        'HEYGEN-9b99ff8586e8402087725968c63ab5dd-V1',
        $source,
        base_path('assets/img/logo-vitrine-ai-pro.png'),
    ));
})->middleware(['throttle:2,1'])->name('marketing.internal.finalize-reel-03');

Route::middleware(['auth'])->group(function () {
    Route::get('/marketing/publisher/meta/connect', function (Request $request) {
        $meta = (array) config('marketing_agents.publisher.meta', []);
        $appId = trim((string) ($meta['app_id'] ?? ''));
        $version = trim((string) ($meta['graph_version'] ?? ''));
        $baseUrl = rtrim((string) ($meta['base_url'] ?? 'https://graph.facebook.com'), '/');
        $redirectUri = trim((string) ($meta['redirect_uri'] ?? '')) ?: route('marketing.publisher.meta.callback');

        if ($appId === '' || trim((string) ($meta['app_secret'] ?? '')) === '' || $version === '') {
            return redirect('/admin/marketing-dashboard#configuracoes')
                ->with('publisher_error', 'Configure o aplicativo Meta antes de conectar uma conta.');
        }

        $state = Str::random(64);
        $request->session()->put('marketing.meta.oauth_state', $state);

        $dialog = $baseUrl.'/'.$version.'/dialog/oauth?'.http_build_query([
            'client_id' => $appId,
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'response_type' => 'code',
            'scope' => implode(',', (array) ($meta['scopes'] ?? [])),
        ]);

        return redirect()->away($dialog);
    })->name('marketing.publisher.meta.connect');

    Route::get('/marketing/publisher/meta/callback', function (Request $request) {
        $expectedState = (string) $request->session()->pull('marketing.meta.oauth_state', '');
        $receivedState = (string) $request->query('state', '');
        abort_unless($expectedState !== '' && hash_equals($expectedState, $receivedState), 419);

        $meta = (array) config('marketing_agents.publisher.meta', []);
        $appId = trim((string) ($meta['app_id'] ?? ''));
        $appSecret = trim((string) ($meta['app_secret'] ?? ''));
        $version = trim((string) ($meta['graph_version'] ?? ''));
        $baseUrl = rtrim((string) ($meta['base_url'] ?? 'https://graph.facebook.com'), '/');
        $redirectUri = trim((string) ($meta['redirect_uri'] ?? '')) ?: route('marketing.publisher.meta.callback');
        $code = trim((string) $request->query('code', ''));

        if ($code === '' || $appId === '' || $appSecret === '' || $version === '') {
            return redirect('/admin/marketing-dashboard#configuracoes')
                ->with('publisher_error', 'A autorização Meta não foi concluída.');
        }

        try {
            $tokenResponse = Http::acceptJson()->timeout(30)->get(
                $baseUrl.'/'.$version.'/oauth/access_token',
                [
                    'client_id' => $appId,
                    'client_secret' => $appSecret,
                    'redirect_uri' => $redirectUri,
                    'code' => $code,
                ]
            );

            if (! $tokenResponse->successful() || trim((string) $tokenResponse->json('access_token')) === '') {
                throw new RuntimeException('Falha ao obter token de autorização Meta.');
            }

            $userToken = trim((string) $tokenResponse->json('access_token'));

            $profileResponse = Http::acceptJson()->timeout(30)->get(
                $baseUrl.'/'.$version.'/me',
                [
                    'fields' => 'id,name',
                    'access_token' => $userToken,
                ]
            );

            if (! $profileResponse->successful() || trim((string) $profileResponse->json('id')) === '') {
                throw new RuntimeException('Falha ao identificar o usuário Meta autorizado.');
            }

            $metaUserId = trim((string) $profileResponse->json('id'));

            $accountsResponse = Http::acceptJson()->timeout(30)->get(
                $baseUrl.'/'.$version.'/me/accounts',
                [
                    'fields' => 'id,name,access_token,instagram_business_account{id,username,name}',
                    'access_token' => $userToken,
                    'limit' => 100,
                ]
            );

            if (! $accountsResponse->successful()) {
                throw new RuntimeException('Falha ao consultar Páginas autorizadas no Meta.');
            }

            $accounts = [];
            foreach ((array) $accountsResponse->json('data', []) as $account) {
                if (! is_array($account) || empty($account['id']) || empty($account['access_token'])) {
                    continue;
                }
                $ig = (array) ($account['instagram_business_account'] ?? []);
                $accounts[] = [
                    'page_id' => (string) $account['id'],
                    'page_name' => (string) ($account['name'] ?? 'Página Meta'),
                    'instagram_user_id' => (string) ($ig['id'] ?? ''),
                    'instagram_username' => (string) ($ig['username'] ?? ''),
                    'instagram_name' => (string) ($ig['name'] ?? ''),
                    'access_token' => (string) $account['access_token'],
                ];
            }

            if ($accounts === []) {
                throw new RuntimeException('Nenhuma Página Meta elegível foi encontrada para esta conta.');
            }

            $scope = auth()->id().'|'.(string) auth()->user()?->company_id;
            $path = 'marketing/publisher/meta/'.hash('sha256', $scope).'.enc';
            Storage::disk('local')->makeDirectory('marketing/publisher/meta');
            Storage::disk('local')->put($path, Crypt::encryptString(json_encode([
                'provider' => 'meta',
                'graph_version' => $version,
                'base_url' => $baseUrl,
                'meta_user_id' => $metaUserId,
                'accounts' => $accounts,
                'selected_page_id' => (string) $accounts[0]['page_id'],
                'connected_at' => now()->toISOString(),
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)));

            return redirect('/admin/marketing-dashboard#configuracoes')
                ->with('publisher_success', 'Conta Meta conectada. Escolha a Página/Instagram padrão para publicar.');
        } catch (Throwable $exception) {
            report($exception);
            return redirect('/admin/marketing-dashboard#configuracoes')
                ->with('publisher_error', 'Não foi possível concluir a conexão Meta: '.$exception->getMessage());
        }
    })->name('marketing.publisher.meta.callback');

    Route::post('/marketing/publisher/meta/disconnect', function () {
        $scope = auth()->id().'|'.(string) auth()->user()?->company_id;
        Storage::disk('local')->delete('marketing/publisher/meta/'.hash('sha256', $scope).'.enc');

        return redirect('/admin/marketing-dashboard#configuracoes')
            ->with('publisher_success', 'Conta Meta desconectada.');
    })->name('marketing.publisher.meta.disconnect');

    Route::get('/marketing/video-preview/reel-01/signed', [VideoPreviewController::class, 'signedUrl'])
        ->middleware(['throttle:10,1'])
        ->name('marketing.video-preview.sign');

    Route::get('/marketing/native-preview/{job}/{version}', [VideoPreviewController::class, 'nativePreview'])
        ->middleware(['signed:relative', 'throttle:300,1'])
        ->where('job', '[A-Za-z0-9._-]+')
        ->where('version', '[A-Za-z0-9._-]+')
        ->name('marketing.native-video-preview');

    Route::get('/marketing/native-image-preview/{generation}', [VideoPreviewController::class, 'nativeImagePreview'])
        ->middleware(['signed:relative', 'throttle:300,1'])
        ->whereNumber('generation')
        ->name('marketing.native-image-preview');

    Route::get('/cliente', [ClientPortalController::class, 'index'])->name('client.portal');

    Route::post('/cliente/logout', function (Request $request) {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/admin/login');
    })->name('client.logout');
});

if (file_exists(__DIR__.'/client_portal_auth.php')) require __DIR__.'/client_portal_auth.php';
if (file_exists(__DIR__.'/client_support_tickets.php')) require __DIR__.'/client_support_tickets.php';
if (file_exists(__DIR__.'/ai_run.php')) require __DIR__.'/ai_run.php';
if (file_exists(__DIR__.'/asaas.php')) require __DIR__.'/asaas.php';
if (file_exists(__DIR__.'/ai_provider_test.php')) require __DIR__.'/ai_provider_test.php';
if (file_exists(__DIR__.'/master_2_0.php')) require __DIR__.'/master_2_0.php';
if (file_exists(__DIR__.'/heygen_callback.php')) require __DIR__.'/heygen_callback.php';
