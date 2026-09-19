<?php

use App\Http\Controllers\Api\CentroIaBrokerController;
use App\Http\Controllers\Api\LeadCaptureController;
use App\Http\Controllers\Api\MarketingDashboardStateController;
use App\Marketing\Domain\Video\VideoProject;
use App\Marketing\Infrastructure\Video\GeminiVeoSceneRenderer;
use App\Models\AiAgent;
use App\Models\AiProvider;
use App\Services\Ai\AiMediaGenerationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::middleware('throttle:60,1')->group(function () {
    Route::post('/leads', [LeadCaptureController::class, 'store'])
        ->name('api.leads.store');
});

Route::middleware('throttle:30,1')->group(function () {
    Route::post('/internal/centro-ia/execute', [CentroIaBrokerController::class, 'execute'])
        ->name('api.internal.centro-ia.execute');

    Route::get('/internal/marketing/dashboard-state', MarketingDashboardStateController::class)
        ->name('api.internal.marketing.dashboard-state');

    Route::post('/internal/marketing/media/image', function (Request $request, AiMediaGenerationService $media) {
        $expectedToken = trim((string) env('MARKETING_ENGINE_TOKEN', ''));
        $receivedToken = trim((string) $request->bearerToken());

        if ($expectedToken === '' || $receivedToken === '' || ! hash_equals($expectedToken, $receivedToken)) {
            return response()->json(['ok' => false, 'error' => 'unauthorized'], 401);
        }

        $data = $request->validate([
            'project_id' => ['required', 'string', 'max:120'],
            'brand' => ['required', 'string', 'max:160'],
            'idea' => ['required', 'string', 'max:4000'],
            'objective' => ['nullable', 'string', 'max:120'],
            'channel' => ['nullable', 'string', 'max:80'],
            'format' => ['nullable', 'string', 'max:80'],
            'title' => ['nullable', 'string', 'max:300'],
            'caption' => ['nullable', 'string', 'max:8000'],
            'cta' => ['nullable', 'string', 'max:1000'],
        ]);

        $agent = AiAgent::query()->where('slug', 'marketing-ia')->first();
        $provider = AiProvider::query()
            ->whereIn('slug', ['google', 'gemini', 'google-gemini'])
            ->where('status', 'ativo')
            ->first();

        if (! $agent || ! $provider) {
            return response()->json(['ok' => false, 'error' => 'image_engine_not_configured'], 503);
        }

        $prompt = 'Crie uma imagem publicitária profissional para redes sociais da marca '.trim($data['brand']).'. '
            .'Tema: '.trim($data['idea']).'. '
            .'Objetivo: '.trim((string) ($data['objective'] ?? 'engagement')).'. '
            .'Canal: '.trim((string) ($data['channel'] ?? 'instagram')).'. '
            .'Formato editorial: '.trim((string) ($data['format'] ?? 'post')).'. '
            .'Título de referência: '.trim((string) ($data['title'] ?? '')).'. '
            .'Mensagem de referência: '.trim((string) ($data['caption'] ?? '')).'. '
            .'CTA de referência: '.trim((string) ($data['cta'] ?? '')).'. '
            .'Gere apenas a base visual. Não invente dados factuais, não recrie logotipo, não use marcas de terceiros e não renderize texto legível na imagem. '
            .'Preserve composição limpa e área segura para aplicação posterior dos elementos de marca.';

        $generation = $media->generate(
            $agent,
            $provider,
            'image_generation',
            $prompt,
            (string) config('marketing_agents.native_studio.image_model', 'gemini-3.1-flash-image'),
        );

        if ((string) $generation->status !== 'Concluído' || ! $generation->asset_path) {
            return response()->json([
                'ok' => false,
                'error' => 'image_generation_failed',
                'message' => (string) ($generation->error_message ?: $generation->output ?: 'Falha na geração de imagem.'),
            ], 502);
        }

        $disk = (string) data_get($generation->metadata, 'storage_disk', config('filesystems.default', 'local'));
        $binary = Storage::disk($disk)->get((string) $generation->asset_path);

        return response()->json([
            'ok' => true,
            'generation_id' => $generation->id,
            'model' => $generation->model_name,
            'mime_type' => (string) data_get($generation->metadata, 'mime_type', 'image/png'),
            'image_base64' => base64_encode($binary),
        ]);
    })->name('api.internal.marketing.media.image');

    Route::post('/internal/marketing/media/video', function (Request $request, GeminiVeoSceneRenderer $renderer) {
        $expectedToken = trim((string) env('MARKETING_ENGINE_TOKEN', ''));
        $receivedToken = trim((string) $request->bearerToken());

        if ($expectedToken === '' || $receivedToken === '' || ! hash_equals($expectedToken, $receivedToken)) {
            return response()->json(['ok' => false, 'error' => 'unauthorized'], 401);
        }

        $data = $request->validate([
            'project_id' => ['required', 'string', 'max:120'],
            'brand' => ['required', 'string', 'max:160'],
            'idea' => ['required', 'string', 'max:4000'],
            'objective' => ['nullable', 'string', 'max:120'],
            'channel' => ['nullable', 'string', 'max:80'],
            'format' => ['nullable', 'string', 'max:80'],
            'title' => ['nullable', 'string', 'max:300'],
            'caption' => ['nullable', 'string', 'max:8000'],
            'cta' => ['nullable', 'string', 'max:1000'],
            'aspect_ratio' => ['nullable', 'in:9:16,16:9'],
            'duration_seconds' => ['nullable', 'integer', 'in:4,6,8'],
        ]);

        $prompt = 'Crie um vídeo curto profissional para redes sociais da marca '.trim($data['brand']).'. '
            .'Tema: '.trim($data['idea']).'. '
            .'Objetivo: '.trim((string) ($data['objective'] ?? 'engagement')).'. '
            .'Canal: '.trim((string) ($data['channel'] ?? 'instagram')).'. '
            .'Formato: '.trim((string) ($data['format'] ?? 'reels')).'. '
            .'Título de referência: '.trim((string) ($data['title'] ?? '')).'. '
            .'Mensagem de referência: '.trim((string) ($data['caption'] ?? '')).'. '
            .'CTA de referência: '.trim((string) ($data['cta'] ?? '')).'. '
            .'Produza uma narrativa visual coerente com a marca e o briefing. '
            .'Não invente dados factuais, não recrie logotipo, não use marcas de terceiros e não renderize textos legíveis. '
            .'Reserve área segura para identidade visual e CTA na finalização.';

        $project = new VideoProject(
            projectId: 'SOCIAL-'.strtoupper(substr(sha1((string) $data['project_id'].'|'.microtime(true)), 0, 12)),
            productId: 'marketing-ia-engine',
            campaignId: (string) str($data['brand'])->slug(),
        );
        $scene = $project->addScene('SCENE-01', 1, ['prompt' => $prompt]);

        $job = $renderer->dispatch($project, $scene, [
            'aspect_ratio' => (string) ($data['aspect_ratio'] ?? '9:16'),
            'duration_seconds' => (int) ($data['duration_seconds'] ?? 8),
            'resolution' => '720p',
        ]);

        return response()->json([
            'ok' => true,
            'provider' => 'gemini_veo',
            'status' => (string) ($job['status'] ?? 'processing'),
            'job_ref' => (string) ($job['job_ref'] ?? ''),
            'model' => (string) config('marketing_video.gemini_veo.model', 'veo-3.1-generate-preview'),
        ]);
    })->name('api.internal.marketing.media.video');

    Route::post('/internal/marketing/media/video/refresh', function (Request $request, GeminiVeoSceneRenderer $renderer) {
        $expectedToken = trim((string) env('MARKETING_ENGINE_TOKEN', ''));
        $receivedToken = trim((string) $request->bearerToken());

        if ($expectedToken === '' || $receivedToken === '' || ! hash_equals($expectedToken, $receivedToken)) {
            return response()->json(['ok' => false, 'error' => 'unauthorized'], 401);
        }

        $data = $request->validate([
            'job_ref' => ['required', 'string', 'max:500'],
        ]);

        $job = $renderer->refresh((string) $data['job_ref']);

        return response()->json([
            'ok' => true,
            'provider' => 'gemini_veo',
            'status' => (string) ($job['status'] ?? 'processing'),
            'job_ref' => (string) ($job['job_ref'] ?? ''),
            'asset_url' => $job['render_ref'] ?? null,
        ]);
    })->name('api.internal.marketing.media.video.refresh');
});

require __DIR__.'/site_factory_api.php';
