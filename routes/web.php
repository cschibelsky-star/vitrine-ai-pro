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

Route::get('/sso/cockpit', function (Request $request) {
    $token = trim((string) $request->query('token', ''));
    abort_unless(strlen($token) === 64, 404);

    $response = Http::acceptJson()->timeout(5)->get('https://hml.vitrineiapro.com.br/cockpit/sso/consume', [
        'token' => $token,
    ]);
    abort_unless($response->successful(), 403, 'cockpit_sso_invalid');

    $payload = (array) $response->json();
    abort_unless(($payload['target'] ?? null) === 'marketing-ia', 403, 'cockpit_sso_target_invalid');
    abort_unless(in_array(($payload['role'] ?? null), ['admin'], true), 403, 'cockpit_sso_role_invalid');

    $email = trim((string) ($payload['email'] ?? ''));
    abort_unless(filter_var($email, FILTER_VALIDATE_EMAIL), 403, 'cockpit_sso_identity_invalid');

    $user = \App\Models\User::query()->where('email', $email)->first();
    abort_unless($user && ($user->is_active ?? true), 403, 'cockpit_sso_user_not_provisioned');

    Auth::login($user, false);
    $request->session()->regenerate();

    return redirect('/admin/marketing-dashboard');
})->middleware('throttle:20,1')->name('marketing.sso.cockpit');

Route::get('/marketing/media/reel-01/{version}', [VideoPreviewController::class, 'publicMedia'])
    ->middleware(['throttle:60,1'])
    ->where('version', '[A-Za-z0-9._-]+\\.mp4')
    ->name('marketing.media.reel-01');

Route::get('/marketing/media/reel-03/{version}', [VideoPreviewController::class, 'publicMediaReel03'])
    ->middleware(['throttle:30,1'])
    ->where('version', '[A-Za-z0-9._-]+\\.mp4')
    ->name('marketing.media.reel-03');

Route::get('/marketing/media/tv-sumare-reel/{slug}.mp4', [VideoPreviewController::class, 'publicTvSumareReel'])
    ->middleware(['throttle:60,1'])
    ->where('slug', '[A-Za-z0-9._-]+')
    ->name('marketing.media.tv-sumare-reel');

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
        'format' => ['nullable', 'in:image,reel'],
    ]);

    return response()->json($publisher->publishTvSumareArticleNow(
        (string) $validated['url'],
        (string) ($validated['format'] ?? 'image'),
    ));
})->middleware(['throttle:10,1'])->name('marketing.internal.publish-tv-sumare-article');

Route::post('/marketing/internal/publish-tv-sumare-facebook', function (Request $request, SocialDistributionHandoff $publisher) {
    $expected = (string) env('MARKETING_ENGINE_TOKEN', '');
    abort_unless($expected !== '' && hash_equals($expected, (string) $request->header('X-Marketing-Engine-Token', '')), 403);

    $validated = $request->validate([
        'url' => ['required', 'url', 'max:2048'],
        'page_id' => ['required', 'string', 'max:80'],
        'format' => ['nullable', 'in:image,reel'],
    ]);

    $meta = (array) config('marketing_agents.publisher.meta', []);
    $token = trim((string) ($meta['access_token'] ?? ''));
    $version = trim((string) ($meta['graph_version'] ?? ''));
    $baseUrl = rtrim((string) ($meta['base_url'] ?? 'https://graph.facebook.com'), '/');
    abort_if($token === '' || $version === '', 503, 'meta_publisher_not_configured');

    $accounts = Http::acceptJson()->timeout(30)->get(
        $baseUrl.'/'.$version.'/me/accounts',
        [
            'fields' => 'id,name,access_token',
            'access_token' => $token,
            'limit' => 100,
        ]
    );

    abort_unless($accounts->successful(), 502, 'meta_accounts_lookup_failed');

    $requestedPageId = (string) $validated['page_id'];
    $page = collect((array) $accounts->json('data', []))->first(
        fn ($item) => is_array($item)
            && (string) ($item['id'] ?? '') === $requestedPageId
            && trim((string) ($item['access_token'] ?? '')) !== ''
    );

    abort_unless(is_array($page), 409, 'facebook_page_not_authorized');

    return response()->json($publisher->publishTvSumareArticleNow(
        (string) $validated['url'],
        (string) ($validated['format'] ?? 'image'),
        [
            'access_token' => (string) $page['access_token'],
            'instagram_user_id' => '',
            'facebook_page_id' => $requestedPageId,
            'graph_version' => $version,
            'base_url' => $baseUrl,
        ],
    ));
})->middleware(['throttle:10,1'])->name('marketing.internal.publish-tv-sumare-facebook');

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


Route::get('/marketing/publisher/meta/bridge/callback', function (Request $request) {
    $state = trim((string) $request->query('state', ''));
    $code = trim((string) $request->query('code', ''));
    abort_if($state === '' || $code === '', 400, 'meta_oauth_callback_invalid');

    $statePath = 'marketing/publisher/meta/bridge/states/'.hash('sha256', $state).'.enc';
    abort_unless(Storage::disk('local')->exists($statePath), 419, 'meta_oauth_state_expired');

    $context = json_decode(
        Crypt::decryptString(Storage::disk('local')->get($statePath)),
        true,
        512,
        JSON_THROW_ON_ERROR
    );
    Storage::disk('local')->delete($statePath);

    $createdAt = isset($context['created_at']) ? \Illuminate\Support\Carbon::parse($context['created_at']) : null;
    abort_if(! $createdAt || $createdAt->lt(now()->subMinutes(15)), 419, 'meta_oauth_state_expired');

    $returnUrl = (string) ($context['return_url'] ?? '');
    $returnHost = strtolower((string) parse_url($returnUrl, PHP_URL_HOST));
    abort_unless(in_array($returnHost, ['social.hml.vitrineiapro.com.br'], true), 422);

    $meta = (array) config('marketing_agents.publisher.meta', []);
    $appId = trim((string) ($meta['app_id'] ?? ''));
    $appSecret = trim((string) ($meta['app_secret'] ?? ''));
    $version = trim((string) ($meta['graph_version'] ?? ''));
    abort_if($appId === '' || $appSecret === '' || $version === '', 503, 'meta_app_not_configured');

    try {
        $callback = url('/marketing/publisher/meta/bridge/callback');
        $tokenResponse = Http::acceptJson()->timeout(30)->get(
            'https://graph.facebook.com/'.$version.'/oauth/access_token',
            [
                'client_id' => $appId,
                'client_secret' => $appSecret,
                'redirect_uri' => $callback,
                'code' => $code,
            ]
        );

        if (! $tokenResponse->successful() || trim((string) $tokenResponse->json('access_token')) === '') {
            throw new RuntimeException('Falha ao obter autorização Meta.');
        }

        $userToken = trim((string) $tokenResponse->json('access_token'));

        $accountsResponse = Http::acceptJson()->timeout(30)->get(
            'https://graph.facebook.com/'.$version.'/me/accounts',
            [
                'fields' => 'id,name,access_token,instagram_business_account{id,username,name}',
                'access_token' => $userToken,
                'limit' => 100,
            ]
        );

        if (! $accountsResponse->successful()) {
            throw new RuntimeException('Falha ao consultar Páginas Meta autorizadas.');
        }

        $accounts = (array) $accountsResponse->json('data', []);
        $account = collect($accounts)->first(fn ($item) => is_array($item) && ! empty($item['id']) && ! empty($item['access_token']));

        if (! is_array($account)) {
            throw new RuntimeException('Nenhuma Página Meta elegível foi encontrada.');
        }

        $instagram = (array) ($account['instagram_business_account'] ?? []);
        $connectionPath = 'marketing/publisher/meta/bridge/connections/'.hash(
            'sha256',
            (string) ($context['project_id'] ?? '').'|'.(int) ($context['client_id'] ?? 0)
        ).'.enc';

        Storage::disk('local')->makeDirectory('marketing/publisher/meta/bridge/connections');
        Storage::disk('local')->put($connectionPath, Crypt::encryptString(json_encode([
            'provider' => 'meta',
            'page_id' => (string) $account['id'],
            'page_name' => (string) ($account['name'] ?? 'Página Meta'),
            'instagram_user_id' => (string) ($instagram['id'] ?? ''),
            'instagram_username' => (string) ($instagram['username'] ?? ''),
            'access_token' => (string) $account['access_token'],
            'graph_version' => $version,
            'connected_at' => now()->toIso8601String(),
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)));

        return redirect()->away($returnUrl.(str_contains($returnUrl, '?') ? '&' : '?').'publisher=connected');
    } catch (Throwable $exception) {
        report($exception);

        return redirect()->away($returnUrl.(str_contains($returnUrl, '?') ? '&' : '?').'publisher=error');
    }
})->middleware('throttle:20,1')->name('marketing.publisher.meta.bridge.callback');

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
