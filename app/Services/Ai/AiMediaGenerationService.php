<?php

namespace App\Services\Ai;

use App\Models\AiAgent;
use App\Models\AiMediaGeneration;
use App\Models\AiProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class AiMediaGenerationService
{
    public function generate(
        AiAgent $agent,
        AiProvider $provider,
        string $capability,
        string $prompt,
        ?string $model = null,
    ): AiMediaGeneration {
        $generation = AiMediaGeneration::create([
            'ai_agent_id' => $agent->id,
            'ai_provider_id' => $provider->id,
            'capability' => $capability,
            'model_name' => $model,
            'status' => 'Processando',
            'input' => $prompt,
            'started_at' => now(),
            'metadata' => [
                'provider_slug' => $provider->slug,
                'phase' => 'dispatching',
            ],
        ]);

        $started = microtime(true);

        try {
            $result = $this->dispatch($provider, $capability, $prompt, $model);
            $durationMs = (int) round((microtime(true) - $started) * 1000);

            $generation->update([
                'status' => $result['status'] ?? 'Pendente',
                'output' => $result['output'] ?? null,
                'operation_id' => $result['operation_id'] ?? null,
                'asset_url' => $result['asset_url'] ?? null,
                'asset_path' => $result['asset_path'] ?? null,
                'metadata' => array_merge((array) $generation->metadata, $result['metadata'] ?? []),
                'duration_ms' => $durationMs,
                'finished_at' => ($result['status'] ?? null) === 'Concluído' ? now() : null,
            ]);
        } catch (Throwable $e) {
            $generation->update([
                'status' => 'Erro',
                'error_message' => $e->getMessage(),
                'output' => $e->getMessage(),
                'duration_ms' => (int) round((microtime(true) - $started) * 1000),
                'finished_at' => now(),
            ]);
        }

        return $generation->refresh();
    }

    protected function dispatch(AiProvider $provider, string $capability, string $prompt, ?string $model): array
    {
        $providerSlug = strtolower((string) $provider->slug);

        if ($providerSlug === 'roteia') {
            return $this->generateRoteiaMedia($provider, $capability, $prompt, $model);
        }

        if ($capability === 'image_generation' && in_array($providerSlug, ['gemini', 'google', 'google-gemini'], true)) {
            return $this->generateGoogleImage($provider, $prompt, $model);
        }

        if ($capability === 'avatar_video' && $providerSlug === 'heygen') {
            return $this->generateHeygenAvatarVideo($provider, $prompt);
        }

        return [
            'status' => 'Pendente',
            'output' => sprintf(
                'Geração de mídia preparada: provider=%s, capability=%s, model=%s. Adapter externo ainda não executado.',
                $provider->slug,
                $capability,
                $model ?: 'default'
            ),
            'metadata' => [
                'adapter_ready' => false,
                'prompt_length' => mb_strlen($prompt),
            ],
        ];
    }

    protected function generateRoteiaMedia(AiProvider $provider, string $capability, string $prompt, ?string $model): array
    {
        $apiKey = trim((string) ($provider->api_key ?? '')) ?: trim((string) env('ROTEIA_API_KEY', ''));
        $baseUrl = rtrim(trim((string) env('ROTEIA_BASE_URL', '')), '/');

        if ($apiKey === '' || $baseUrl === '') {
            throw new RuntimeException('Roteia não configurado no runtime do Core.');
        }

        // Contrato central do Core. O path pode ser ajustado no cadastro do provedor
        // quando a documentação/conta Roteia definir um endpoint de mídia específico.
        $path = trim((string) data_get($provider->config, 'endpoints.'.$capability, ''));
        if ($path === '') {
            throw new RuntimeException('Endpoint Roteia para '.$capability.' ainda não configurado.');
        }

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->timeout(120)
            ->post($baseUrl.'/'.ltrim($path, '/'), [
                'model' => $model,
                'prompt' => $prompt,
                'capability' => $capability,
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Roteia mídia erro HTTP '.$response->status().': '.$response->body());
        }

        $payload = (array) $response->json();
        $statusRaw = strtolower((string) ($payload['status'] ?? 'pending'));
        $done = in_array($statusRaw, ['completed', 'concluido', 'concluído', 'done', 'success'], true);

        return [
            'status' => $done ? 'Concluído' : 'Pendente',
            'output' => (string) ($payload['message'] ?? 'Geração encaminhada ao Roteia.'),
            'operation_id' => (string) ($payload['operation_id'] ?? $payload['job_id'] ?? $payload['id'] ?? ''),
            'asset_url' => $payload['asset_url'] ?? $payload['url'] ?? null,
            'metadata' => [
                'adapter_ready' => true,
                'adapter' => 'roteia_media',
                'provider_status' => $statusRaw,
                'model' => $model,
            ],
        ];
    }

    protected function generateHeygenAvatarVideo(AiProvider $provider, string $prompt): array
    {
        $apiKey = trim((string) ($provider->api_key ?? '')) ?: trim((string) env('HEYGEN_API_KEY', ''));

        if ($apiKey === '') {
            throw new RuntimeException('API Key HeyGen ausente para avatar_video.');
        }

        $config = is_array($provider->config) ? $provider->config : [];
        $payload = [
            'prompt' => $prompt,
            'mode' => 'generate',
            'incognito_mode' => true,
        ];

        foreach ([
            'avatar_id' => 'avatar_id',
            'voice_id' => 'voice_id',
            'style_id' => 'style_id',
            'brand_kit_id' => 'brand_kit_id',
        ] as $apiField => $configField) {
            $value = trim((string) data_get($config, $configField, ''));
            if ($value !== '') {
                $payload[$apiField] = $value;
            }
        }

        $response = Http::withHeaders([
                'X-Api-Key' => $apiKey,
                'Accept' => 'application/json',
            ])
            ->timeout(60)
            ->post('https://api.heygen.com/v3/video-agents', $payload);

        if ($response->failed()) {
            throw new RuntimeException('HeyGen Video Agent erro HTTP '.$response->status().': '.$response->body());
        }

        $body = (array) $response->json();
        $data = (array) data_get($body, 'data', $body);
        $sessionId = trim((string) ($data['session_id'] ?? ''));
        $videoId = trim((string) ($data['video_id'] ?? ''));

        if ($sessionId === '' && $videoId === '') {
            throw new RuntimeException('HeyGen Video Agent não retornou session_id nem video_id.');
        }

        return [
            'status' => 'Pendente',
            'output' => 'HeyGen Video Agent iniciado.',
            'operation_id' => $sessionId !== '' ? $sessionId : $videoId,
            'metadata' => [
                'adapter_ready' => true,
                'adapter' => 'heygen_video_agent_v3',
                'provider_status' => strtolower((string) ($data['status'] ?? 'generating')),
                'session_id' => $sessionId,
                'video_id' => $videoId,
            ],
        ];
    }

    public function refreshHeygenAvatarVideo(string $sessionId): array
    {
        $provider = AiProvider::query()->where('slug', 'heygen')->where('status', 'ativo')->first();
        $apiKey = trim((string) ($provider?->api_key ?? '')) ?: trim((string) env('HEYGEN_API_KEY', ''));

        if ($apiKey === '') {
            throw new RuntimeException('API Key HeyGen ausente para atualização de avatar_video.');
        }

        $sessionId = trim($sessionId);
        if ($sessionId === '') {
            throw new RuntimeException('session_id HeyGen ausente.');
        }

        $headers = [
            'X-Api-Key' => $apiKey,
            'Accept' => 'application/json',
        ];

        $sessionResponse = Http::withHeaders($headers)
            ->timeout(30)
            ->get('https://api.heygen.com/v3/video-agents/'.rawurlencode($sessionId));

        if ($sessionResponse->failed()) {
            throw new RuntimeException('HeyGen sessão erro HTTP '.$sessionResponse->status().': '.$sessionResponse->body());
        }

        $sessionBody = (array) $sessionResponse->json();
        $session = (array) data_get($sessionBody, 'data', $sessionBody);
        $videoId = trim((string) ($session['video_id'] ?? ''));
        $sessionStatus = strtolower(trim((string) ($session['status'] ?? 'generating')));

        if ($videoId === '') {
            return [
                'status' => in_array($sessionStatus, ['failed', 'error'], true) ? 'failed' : 'processing',
                'job_ref' => $sessionId,
                'video_id' => null,
                'asset_url' => null,
                'provider_status' => $sessionStatus,
            ];
        }

        $videoResponse = Http::withHeaders($headers)
            ->timeout(30)
            ->get('https://api.heygen.com/v3/videos/'.rawurlencode($videoId));

        if ($videoResponse->failed()) {
            throw new RuntimeException('HeyGen vídeo erro HTTP '.$videoResponse->status().': '.$videoResponse->body());
        }

        $videoBody = (array) $videoResponse->json();
        $video = (array) data_get($videoBody, 'data', $videoBody);
        $videoStatus = strtolower(trim((string) ($video['status'] ?? 'processing')));
        $assetUrl = trim((string) ($video['video_url'] ?? ''));

        return [
            'status' => in_array($videoStatus, ['failed', 'error'], true)
                ? 'failed'
                : ($assetUrl !== '' || in_array($videoStatus, ['completed', 'complete', 'done', 'success'], true) ? 'completed' : 'processing'),
            'job_ref' => $sessionId,
            'video_id' => $videoId,
            'asset_url' => $assetUrl !== '' ? $assetUrl : null,
            'provider_status' => $videoStatus,
            'failure_message' => (string) ($video['failure_message'] ?? $video['failure_code'] ?? ''),
        ];
    }

    protected function generateGoogleImage(AiProvider $provider, string $prompt, ?string $model): array
    {
        $apiKey = $this->resolveGeminiApiKey($provider);
        $model = $model
            ?: data_get($provider->config, 'models.image_generation')
            ?: 'gemini-3.1-flash-image';

        if (! $apiKey) {
            throw new RuntimeException('API Key Gemini ausente para geração de imagem.');
        }

        $response = Http::acceptJson()
            ->withHeaders(['x-goog-api-key' => $apiKey])
            ->timeout(120)
            ->retry(2, 500, throw: false)
            ->post('https://generativelanguage.googleapis.com/v1beta/interactions', [
                'model' => $model,
                'input' => [
                    ['type' => 'text', 'text' => $prompt],
                ],
                'response_format' => [
                    'type' => 'image',
                    'mime_type' => 'image/png',
                    'aspect_ratio' => '1:1',
                    'image_size' => '1K',
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Gemini Image erro HTTP '.$response->status().': '.$response->body());
        }

        $payload = $response->json();
        [$base64, $mimeType] = $this->extractImage($payload);

        if (! $base64) {
            throw new RuntimeException('Gemini Image não retornou dados de imagem utilizáveis.');
        }

        $binary = base64_decode($base64, true);

        if ($binary === false || $binary === '') {
            throw new RuntimeException('Gemini Image retornou base64 inválido.');
        }

        $extension = match ($mimeType) {
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/webp' => 'webp',
            default => 'png',
        };

        $disk = config('filesystems.default', 'local');
        $path = 'ai-generated/marketing/'.now()->format('Y/m/d').'/'.Str::uuid().'.'.$extension;

        if (! Storage::disk($disk)->put($path, $binary)) {
            throw new RuntimeException('Falha ao salvar a imagem gerada no filesystem.');
        }

        $assetUrl = null;

        try {
            $assetUrl = Storage::disk($disk)->url($path);
        } catch (Throwable) {
            // Discos privados/locais podem não expor URL pública.
        }

        return [
            'status' => 'Concluído',
            'output' => 'Imagem gerada com sucesso pelo Google Nano Banana.',
            'operation_id' => is_string(data_get($payload, 'id')) ? data_get($payload, 'id') : null,
            'asset_path' => $path,
            'asset_url' => $assetUrl,
            'metadata' => [
                'adapter_ready' => true,
                'adapter' => 'google_interactions_image',
                'model' => $model,
                'mime_type' => $mimeType,
                'storage_disk' => $disk,
                'prompt_length' => mb_strlen($prompt),
                'synthid_expected' => true,
            ],
        ];
    }

    protected function resolveGeminiApiKey(AiProvider $provider): ?string
    {
        $stored = trim((string) ($provider->api_key ?? ''));

        if ($stored !== '') {
            return $stored;
        }

        return env('GEMINI_API_KEY')
            ?: env('GOOGLE_API_KEY')
            ?: env('GOOGLE_GEMINI_API_KEY')
            ?: null;
    }

    protected function extractImage(array $payload): array
    {
        $outputImage = data_get($payload, 'output_image');

        if (is_array($outputImage) && ! empty($outputImage['data'])) {
            return [
                (string) $outputImage['data'],
                (string) ($outputImage['mime_type'] ?? 'image/png'),
            ];
        }

        foreach ((array) data_get($payload, 'steps', []) as $step) {
            foreach ((array) ($step['content'] ?? []) as $content) {
                if (($content['type'] ?? null) !== 'image' || empty($content['data'])) {
                    continue;
                }

                return [
                    (string) $content['data'],
                    (string) ($content['mime_type'] ?? 'image/png'),
                ];
            }
        }

        return [null, null];
    }
}
