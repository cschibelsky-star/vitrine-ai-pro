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

// Endpoint exclusivo para integracao servidor-a-servidor com Assessor Online HML.
// Nao reutiliza a rota legada de consumo de tickets por GET.
Route::post('/cockpit/sso/assessor/consume', function (Request $request) {
    $secret = (string) env('ASSESSOR_COCKPIT_SSO_SECRET', '');
    abort_unless(config('app.env') === 'homologation' && strlen($secret) >= 32, 503);

    $ticket = (string) $request->input('token', '');
    $timestamp = (string) $request->header('X-Assessor-Timestamp', '');
    $signature = (string) $request->header('X-Assessor-Signature', '');
    abort_unless(preg_match('/^[A-Za-z0-9]{64}$/D', $ticket), 401);
    abort_unless(ctype_digit($timestamp) && abs(time() - (int) $timestamp) <= 30, 401);
    $expected = hash_hmac('sha256', "assessor-hml\n".$timestamp."\n".$ticket, $secret);
    abort_unless(preg_match('/^[a-f0-9]{64}$/D', $signature)
        && hash_equals($expected, $signature), 401);

    $digest = hash('sha256', $ticket);
    $key = 'cockpit_sso_ticket:'.$digest;
    // Serialize ticket consumption to prevent concurrent replay.
    $payload = Cache::lock('cockpit_sso_consume_lock:'.$digest, 10)->block(2, function () use ($key) {
        $candidate = Cache::get($key);
        if (! is_array($candidate) || ($candidate['target'] ?? '') !== 'assessor'
            || ! isset($candidate['issued_at'])
            || abs(time() - (int) $candidate['issued_at']) > 60) {
            return null;
        }
        return Cache::pull($key);
    });
    abort_unless(is_array($payload) && ($payload['target'] ?? '') === 'assessor', 401);
    return response()->json([
        'email' => $payload['email'],
        'target' => 'assessor',
        'issued_at' => $payload['issued_at'],
    ])->header('Cache-Control', 'no-store');
})->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class])
    ->middleware('throttle:10,1')->name('cockpit.sso.assessor.consume');

Route::get('/cockpit/sso/consume', function (Request $request) {
    $token = (string) $request->query('token', '');
    abort_unless(strlen($token) === 64, 404);

    $key = 'cockpit_sso_ticket:'.hash('sha256', $token);
    $candidate = Cache::get($key);
    // Assessor tickets require authenticated service-to-service POST.
    abort_unless(is_array($candidate) && ($candidate['target'] ?? '') !== 'assessor', 404);
    $payload = Cache::pull($key);
    abort_unless(is_array($payload) && ($payload['target'] ?? '') !== 'assessor', 404);

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

// Escritório Virtual DEV/ECO — registro e histórico persistentes (MVP).
Route::middleware(['auth'])->group(function () {
    Route::get('/cockpit/escritorio-agentes/estado', \App\Http\Controllers\Cockpit\OfficeOperationalStateController::class)->middleware('throttle:60,1')->name('cockpit.escritorio-agentes.state');

    Route::get('/cockpit/escritorio-operacional', function () {
        abort_unless(config('app.env') === 'homologation', 404);
        $user = Auth::user();
        abort_unless($user && ($user->is_active ?? true) && $user->isAdmin(), 403);
        return response()->view('cockpit.escritorio-operacional');
    })->middleware('throttle:60,1')->name('cockpit.escritorio-operacional');

    /*
     * ⭐⭐⭐ A EXECUÇÃO DO AGENTE — a primeira inferência real do Escritório.
     *
     * ⭐⭐⭐ E O QUE ESTA ROTA NÃO FAZ, QUE É O MAIS IMPORTANTE:
     *
     * ⭐⭐⭐ ELA NÃO REGISTRA A ENTREGA.
     *
     * ⭐⭐ A execução produz TEXTO e o devolve. ⭐⭐ Registrar a entrega — e,
     * com isso, mover a missão para a revisão da VIA — é o trabalho do
     * `/resultado`, que é um caminho SEPARADO e explícito.
     *
     * ⭐⭐⭐ POR QUE ESSA SEPARAÇÃO É O PONTO:
     *
     * ⭐ Se a execução registrasse a entrega, ⭐ o agente produziria E se
     * autoentregaria — ⭐ e a supervisão da VIA perderia o sentido: ⭐ bastaria
     * uma chamada de IA para um trabalho entrar na fila de julgamento, ⭐ sem
     * ninguém decidir que ele deveria ser julgado.
     *
     * ⭐⭐ Na prática: ⭐ a IA produz, ⭐ o gestor revisa o que foi produzido, ⭐
     * registra a entrega, ⭐ e AÍ a VIA julga. ⭐ Quatro mãos distintas.
     *
     * ⭐⭐ E A RESPOSTA TRAZ A EVIDÊNCIA — ⭐ porque é ela que liga o trabalho
     * ao que a VIA vai auditar.
     */
    Route::post('/cockpit/escritorio-agentes/missoes/{mission}/executar', function (\App\Models\OfficeMission $mission, \App\Marketing\Domain\Agents\AgentTaskExecutor $executor, \App\Marketing\Domain\Agents\AvelloExecutionAdapter $avello) {
        abort_unless(config('app.env') === 'homologation', 404);
        $user = Auth::user();
        abort_unless($user && ($user->is_active ?? true) && $user->isAdmin(), 403);

        // ⭐⭐ A MISSÃO PRECISA ESTAR PRODUZIDA POR UM AGENTE.
        abort_unless(
            ($mission->metadata['result_submitted_by'] ?? null) !== null
                || ($mission->metadata['agent_name'] ?? null) !== null,
            422,
            'A missão não tem agente responsável. Registre o resultado antes de executar.'
        );

        $contexto = [
            'mission_id' => (string) $mission->id,
            'request_id' => (string) Str::uuid(),
            'domain' => (string) config('marketing_agents.avello.domain', 'ECO'),
            'agent_name' => (string) ($mission->metadata['agent_name'] ?? 'Agente'),
            'department' => (string) ($mission->metadata['department'] ?? 'operacional'),
            'step' => (string) ($mission->metadata['review_status'] ?? 'executando'),
            'task_title' => (string) $mission->title,
            'previous_via_note' => $mission->metadata['review_note'] ?? null,
        ];

        // ⭐⭐ O AVELLO É O EXECUTOR INICIAL. ⭐ Se estiver desligado, o RoteirO
        // assume — e é por isso que o `RoutedModelProvider` existe.
        $provedor = $avello->supports((string) config('marketing_agents.avello.model_default'))
            ? $avello
            : $executor->provider();

        $resultado = $provedor->execute(
            (string) config('marketing_agents.avello.model_default'),
            (string) $mission->description,
            $contexto,
        );

        // ⭐⭐ O TEXTO VOLTA PARA O GESTOR — ⭐ e NÃO para a VIA.
        return response()->json([
            'texto' => $resultado->text,
            'modelo' => $resultado->modelUsed,
            'tokens_entrada' => $resultado->inputTokens,
            'tokens_saida' => $resultado->outputTokens,
            'custo_centavos' => $resultado->costCents,
            'duracao_ms' => $resultado->durationMs,
            'provedor' => $provedor->name(),
        ]);
    })->middleware('throttle:10,1')->name('cockpit.escritorio-agentes.missoes.execute');

    Route::put('/cockpit/escritorio-agentes/missoes/{mission}/resultado', function (\App\Models\OfficeMission $mission, \App\Http\Requests\StoreAdministrativeMinutesResultRequest $request, \App\Services\Office\AdministrativeMinutesService $service) {
        abort_unless(config('app.env') === 'homologation', 404);
        $user = Auth::user();
        abort_unless($user && ($user->is_active ?? true) && $user->isAdmin(), 403);

        $service->submitResult($mission, $request, (int) $user->id);

        return redirect()->route('cockpit.escritorio-agentes.index')
            ->with('status', 'Resultado da ata registrado para revisão do gestor.');
    })->middleware('throttle:20,1')->name('cockpit.escritorio-agentes.missoes.result.store');

    /*
     * ⭐⭐⭐ A DECISÃO DA VIA.
     *
     * ⭐⭐⭐ AQUI ESTÁ A SEPARAÇÃO QUE A AUDITORIA EXIGIU.
     *
     * ⭐⭐ A rota antiga (`/revisao`) usava o MESMO usuário que podia registrar o
     * resultado — ou seja, ⭐ quem produzia a entrega podia aprová-la. ⭐ A
     * supervisão era decorativa: a regra do Escritório existiria no código, mas
     * nada a impedia de contornada por um clique.
     *
     * ⭐⭐ AGORA a decisão exige um SUPERVISOR DA VIA explícito, que:
     *   - precisa existir, estar ativo e ter permissão de supervisão;
     *   - ⭐⭐ NÃO pode ser quem produziu a entrega;
     *   - ⭐⭐ NÃO pode ser quem criou a missão (quando não há entrega registrada).
     *
     * ⭐⭐ E quem produz a entrega (`/resultado`) continua passando por outro
     * caminho — então produz e julgar são, de fato, ações distintas.
     */
    Route::patch('/cockpit/escritorio-agentes/missoes/{mission}/decisao-via', function (\App\Models\OfficeMission $mission, \App\Http\Requests\DecideViaRequest $request, \App\Marketing\Domain\Agents\ViaDecisionService $via) {
        abort_unless(config('app.env') === 'homologation', 404);
        $user = Auth::user();
        abort_unless($user && ($user->is_active ?? true) && $user->isAdmin(), 403);

        $aprovado = $request->validated('decision') === 'approve';

        // ⭐⭐ A DECISÃO.
        //
        // ⭐⭐ O `ViaDecisionService` aplica a regra e recusa com `abort()`:
        //   - 409 quando a entrega não está em revisão (conflito de estado);
        //   - ⭐ 403 quando o supervisor produziu a entrega que ia julgar.
        //
        // ⭐⭐ São status codes HTTP porque ⭐ a resposta ao gestor precisa
        // distinguir "já foi julgada" de "você não pode julgar isto" — e uma
        // tela que mostra "erro" para os dois não ajuda ninguém.
        $via->decide(
            mission: $mission,
            aprovado: $aprovado,
            parecer: $request->validated('note'),
            supervisorId: (int) $request->validated('via_supervisor_id'),
            atorId: (int) $user->id,
        );

        return redirect()->route('cockpit.escritorio-agentes.index')
            ->with('status', $aprovado
                ? 'Decisão da VIA registrada: entrega liberada.'
                : 'Decisão da VIA registrada: a entrega voltou ao mesmo agente.');
    })->middleware('throttle:20,1')->name('cockpit.escritorio-agentes.missoes.decisao-via');

    Route::post('/cockpit/escritorio-agentes/atas', function (\App\Http\Requests\StoreAdministrativeMinutesRequest $request, \App\Services\Office\AdministrativeMinutesService $service) {
        abort_unless(config('app.env') === 'homologation', 404);
        $user = Auth::user();
        abort_unless($user && ($user->is_active ?? true) && $user->isAdmin(), 403);

        $service->create($request, (int) $user->id);

        return redirect()->route('cockpit.escritorio-agentes.index')
            ->with('status', 'Ata registrada para revisão do gestor. Nenhuma execução externa foi disparada.');
    })->middleware('throttle:20,1')->name('cockpit.escritorio-agentes.atas.store');

    Route::get('/cockpit/escritorio-agentes', function () {
        abort_unless(config('app.env') === 'homologation', 404);
        $user = Auth::user();
        abort_unless($user && ($user->is_active ?? true) && $user->isAdmin(), 403);

        $missions = \App\Models\OfficeMission::query()
            ->with(['events.actor'])
            ->where('created_by', $user->id)
            ->latest()
            ->limit(100)
            ->get();

        $counts = [
            'planned' => $missions->where('status', 'planned')->count(),
            'executing' => $missions->where('status', 'executing')->count(),
            'awaiting' => $missions->where('status', 'awaiting_independent_verification')->count(),
            'completed' => $missions->where('status', 'completed')->count(),
        ];

        return response()->view('cockpit.escritorio-agentes', compact('missions', 'counts'))
            ->header('Cache-Control', 'private, no-store, max-age=0')
            ->header('X-Content-Type-Options', 'nosniff')
            ->header('X-Frame-Options', 'DENY');
    })->middleware('throttle:60,1')->name('cockpit.escritorio-agentes.index');

    Route::post('/cockpit/escritorio-agentes/missoes', function (Request $request) {
        abort_unless(config('app.env') === 'homologation', 404);
        $user = Auth::user();
        abort_unless($user && ($user->is_active ?? true) && $user->isAdmin(), 403);

        $data = $request->validate([
            'domain' => ['required', 'in:DEV,ECO'],
            'title' => ['required', 'string', 'min:4', 'max:180'],
            'description' => ['required', 'string', 'min:8', 'max:5000'],
            'priority' => ['required', 'in:low,normal,high'],
            'idempotency_key' => ['required', 'uuid'],
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($data, $user) {
            $mission = \App\Models\OfficeMission::firstOrCreate(
                ['idempotency_key' => $data['idempotency_key']],
                [
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'created_by' => $user->id,
                    'domain' => $data['domain'],
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'priority' => $data['priority'],
                    'status' => 'planned',
                    'metadata' => ['executor' => null, 'n8n_execution_id' => null, 'via_decision' => 'not_submitted'],
                ]
            );

            abort_unless((int) $mission->created_by === (int) $user->id, 409, 'Chave de idempotencia ja utilizada.');

            if ($mission->wasRecentlyCreated) {
                $mission->events()->create([
                    'actor_id' => $user->id,
                    'event_type' => 'mission_created',
                    'from_status' => null,
                    'to_status' => 'planned',
                    'request_id' => $data['idempotency_key'],
                    'payload' => ['source' => 'office_virtual_ui'],
                ]);
            }
        });

        return redirect()->route('cockpit.escritorio-agentes.index')
            ->with('status', 'Missão registrada. Ela ainda não foi enviada ao n8n, Avello ou VIA.');
    })->middleware('throttle:20,1')->name('cockpit.escritorio-agentes.missoes.store');
});

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


Route::get('/cockpit/centro-ia', function () {
    abort_unless(Auth::check() && Auth::user()->isAdmin() && (Auth::user()->is_active ?? true), 403);
    $root = '/mnt/centro-ia-pr122-assets/ai-generated/marketing';
    $available = is_dir($root) && is_readable($root);
    $images = [];
    if ($available) {
        $base = realpath($root);
        foreach (glob($root.'/*/*/*/*.{png,jpg,webp}', GLOB_BRACE) ?: [] as $file) {
            $real = realpath($file);
            if (!$real || !str_starts_with($real, $base.DIRECTORY_SEPARATOR)) continue;
            $relative = substr($real, strlen($base) + 1);
            if (!preg_match('~^([0-9]{4})/([0-9]{2})/([0-9]{2})/([a-f0-9-]{36}\.(?:png|jpg|webp))$~D', $relative, $parts)) continue;
            $size = filesize($real);
            if (!$size || $size > 20 * 1024 * 1024) continue;
            $images[] = ['year'=>$parts[1], 'month'=>$parts[2], 'day'=>$parts[3], 'filename'=>$parts[4], 'bytes'=>$size, 'mtime'=>filemtime($real) ?: 0];
        }
        usort($images, fn($a, $b) => $b['mtime'] <=> $a['mtime']);
        $images = array_slice($images, 0, 12);
    }
    return view('cockpit.centro-ia-gallery', compact('available','images'));
})->name('cockpit.centro-ia.index');

Route::get('/cockpit/centro-ia/image/{year}/{month}/{day}/{filename}', function (string $year, string $month, string $day, string $filename) {
    abort_unless(Auth::check() && Auth::user()->isAdmin() && (Auth::user()->is_active ?? true), 403);
    abort_unless(preg_match('~^[a-f0-9-]{36}\.(?:png|jpg|webp)$~D', $filename), 404);
    $root = realpath('/mnt/centro-ia-pr122-assets/ai-generated/marketing');
    abort_unless($root !== false, 404);
    $file = realpath($root.'/'.$year.'/'.$month.'/'.$day.'/'.$filename);
    abort_unless($file !== false && str_starts_with($file, $root.DIRECTORY_SEPARATOR), 404);
    abort_unless(is_file($file) && is_readable($file), 404);
    $size = filesize($file);
    abort_unless($size !== false && $size > 0 && $size <= 20 * 1024 * 1024, 404);
    $bytes = file_get_contents($file);
    abort_unless($bytes !== false, 404);
    $info = @getimagesizefromstring($bytes);
    $mime = is_array($info) ? ($info['mime'] ?? '') : '';
    abort_unless(in_array($mime, ['image/png','image/jpeg','image/webp'], true), 404);
    return response($bytes, 200, ['Content-Type'=>$mime, 'Content-Disposition'=>'inline', 'Cache-Control'=>'private, no-store, max-age=0', 'X-Content-Type-Options'=>'nosniff']);
})->where(['year'=>'[0-9]{4}','month'=>'[0-9]{2}','day'=>'[0-9]{2}'])->name('cockpit.centro-ia.image');

Route::post('/cockpit/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('cockpit.login');
})->name('cockpit.logout');
