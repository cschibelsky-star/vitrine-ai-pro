<?php

use App\Http\Controllers\Api\CentroIaBrokerController;
use App\Http\Controllers\Api\LeadCaptureController;
use App\Http\Controllers\Api\MarketingDashboardStateController;
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
});

require __DIR__.'/site_factory_api.php';
