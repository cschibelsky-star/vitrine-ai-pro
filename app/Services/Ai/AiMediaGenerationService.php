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
        array $options = [],
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
            $result = $this->dispatch($provider, $capability, $prompt, $model, $options);
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

    protected function dispatch(AiProvider $provider, string $capability, string $prompt, ?string $model, array $options = []): array
    {
        $providerSlug = strtolower((string) $provider->slug);

        if ($capability === 'image_generation' && in_array($providerSlug, ['gemini', 'google', 'google-gemini'], true)) {
            return $this->generateGoogleImage($provider, $prompt, $model, $options);
        }

        if ($capability === 'video_generation' && in_array($providerSlug, ['gemini', 'google', 'google-gemini'], true)) {
            return $this->generateGoogleVideo($provider, $prompt, $model);
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

    protected function generateGoogleImage(AiProvider $provider, string $prompt, ?string $model, array $options = []): array
    {
        $aspectRatio = in_array((string) ($options['aspect_ratio'] ?? '1:1'), ['1:1', '9:16', '16:9'], true)
            ? (string) ($options['aspect_ratio'] ?? '1:1')
            : '1:1';

        try {
            return $this->generateImageThroughCentroIa($prompt, $aspectRatio);
        } catch (Throwable $routingException) {
            throw new RuntimeException(
                'centro_ia_image_dispatch_failed:'.$routingException->getMessage(),
                0,
                $routingException
            );
        }
    }

    protected function generateImageThroughCentroIa(string $prompt, string $aspectRatio = '1:1'): array
    {
        $hub = (array) config('marketing_agents.hub', []);
        $url = trim((string) ($hub['url'] ?? ''));
        $token = trim((string) ($hub['token'] ?? ''));
        $projectId = trim((string) ($hub['project_id'] ?? 'vitrine-marketing-agents-core'));

        if ($url === '' || $token === '') {
            throw new RuntimeException('Centro IA indisponível para roteamento dinâmico de imagem.');
        }

        $response = Http::acceptJson()
            ->asJson()
            ->withToken($token)
            ->withHeaders(['X-Vitrine-Project' => $projectId])
            ->timeout(150)
            ->retry(1, 300, throw: false)
            ->post($url, [
                'project_id' => $projectId,
                'capability' => 'image_generation',
                'input' => [
                    'user' => $prompt,
                    'material_type' => 'social_creative',
                    'quality_profile' => 'balanced',
                    'aspect_ratio' => $aspectRatio,
                ],
            ]);

        if (! $response->successful() || ! $response->json('ok')) {
            throw new RuntimeException(
                'Orquestrador dinâmico de imagem falhou: '.
                (string) ($response->json('error') ?? ('HTTP '.$response->status()))
            );
        }

        $base64 = trim((string) $response->json('asset_base64', ''));
        $assetUrl = trim((string) $response->json('asset_url', ''));
        $binary = null;
        $mimeType = 'image/jpeg';

        if ($base64 !== '') {
            $decoded = base64_decode($base64, true);
            if ($decoded !== false && $decoded !== '') {
                $binary = $decoded;
            }
        }

        if ($binary === null && $assetUrl !== '') {
            $assetResponse = Http::timeout(90)->retry(1, 300, throw: false)->get($assetUrl);
            if ($assetResponse->successful() && $assetResponse->body() !== '') {
                $binary = $assetResponse->body();
                $mimeType = trim((string) $assetResponse->header('Content-Type', 'image/jpeg')) ?: 'image/jpeg';
            }
        }

        if (! is_string($binary) || $binary === '') {
            throw new RuntimeException('Fallback Roteia concluiu, mas não retornou uma imagem utilizável.');
        }

        $extension = str_contains(strtolower($mimeType), 'png') ? 'png'
            : (str_contains(strtolower($mimeType), 'webp') ? 'webp' : 'jpg');
        $disk = config('filesystems.default', 'local');
        $path = 'ai-generated/marketing/'.now()->format('Y/m/d').'/'.Str::uuid().'.'.$extension;

        if (! Storage::disk($disk)->put($path, $binary)) {
            throw new RuntimeException('Falha ao salvar a imagem recebida pelo fallback Roteia.');
        }

        return [
            'status' => 'Concluído',
            'output' => 'Imagem gerada pelo orquestrador dinâmico do Centro IA.',
            'operation_id' => null,
            'asset_path' => $path,
            'asset_url' => null,
            'metadata' => [
                'adapter_ready' => true,
                'adapter' => 'centro_ia_roteia_image_fallback',
                'model' => (string) $response->json('model', 'google/gemini-3.1-flash-image'),
                'mime_type' => $mimeType,
                'storage_disk' => $disk,
                'prompt_length' => mb_strlen($prompt),
                'branding' => 'client_branding_pending',
                'flow_dependency' => false,
            ],
        ];
    }

    protected function applyOfficialLogoToImage(string $binary): string
    {
        if (! function_exists('imagecreatefromstring')) {
            throw new RuntimeException('Extensão GD indisponível para branding da imagem.');
        }

        $base = @imagecreatefromstring($binary);
        $logoPath = (string) config('marketing_video.finalization.official_logo_path', base_path('assets/img/logo-vitrine-ai-pro.png'));
        $logo = is_file($logoPath) ? @imagecreatefrompng($logoPath) : false;

        if ($base === false || $logo === false) {
            throw new RuntimeException('Não foi possível aplicar o logo oficial ao criativo.');
        }

        imagealphablending($base, true);
        imagesavealpha($base, true);

        $baseWidth = imagesx($base);
        $baseHeight = imagesy($base);
        $logoWidth = imagesx($logo);
        $logoHeight = imagesy($logo);
        $targetWidth = max(96, (int) round($baseWidth * 0.18));
        $targetHeight = max(1, (int) round($logoHeight * ($targetWidth / max(1, $logoWidth))));
        $margin = max(24, (int) round($baseWidth * 0.045));
        $x = max(0, $baseWidth - $targetWidth - $margin);
        $y = $margin;

        imagecopyresampled(
            $base,
            $logo,
            $x,
            $y,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $logoWidth,
            $logoHeight,
        );

        ob_start();
        imagepng($base, null, 6);
        $result = ob_get_clean();

        imagedestroy($logo);
        imagedestroy($base);

        if (! is_string($result) || $result === '') {
            throw new RuntimeException('Falha ao serializar o criativo final com branding.');
        }

        return $result;
    }

    protected function generateGoogleVideo(AiProvider $provider, string $prompt, ?string $model): array
    {
        $apiKey = $this->resolveGeminiApiKey($provider);
        $model = $model
            ?: data_get($provider->config, 'models.video_generation')
            ?: config('marketing_video.gemini_veo.model', 'veo-3.1-generate-preview');

        if (! $apiKey) {
            throw new RuntimeException('API Key Gemini ausente para geração de vídeo.');
        }

        $baseUrl = rtrim((string) config('marketing_video.gemini_veo.base_url', 'https://generativelanguage.googleapis.com/v1beta'), '/');
        $aspectRatio = (string) config('marketing_video.gemini_veo.aspect_ratio', '9:16');
        $resolution = (string) config('marketing_video.gemini_veo.resolution', '720p');
        $duration = (int) config('marketing_video.gemini_veo.duration_seconds', 8);

        $response = Http::baseUrl($baseUrl)
            ->acceptJson()
            ->withHeaders(['x-goog-api-key' => $apiKey])
            ->timeout((int) config('marketing_video.gemini_veo.timeout_seconds', 30))
            ->retry((int) config('marketing_video.gemini_veo.http_retries', 2), (int) config('marketing_video.gemini_veo.http_retry_delay_ms', 500), throw: false)
            ->post('/models/'.$model.':predictLongRunning', [
                'instances' => [[
                    'prompt' => $prompt,
                ]],
                'parameters' => [
                    'aspectRatio' => $aspectRatio,
                    'resolution' => $resolution,
                    'durationSeconds' => $duration,
                    'sampleCount' => 1,
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Gemini Veo erro HTTP '.$response->status().'.');
        }

        $operation = trim((string) $response->json('name', ''));

        if ($operation === '') {
            throw new RuntimeException('Gemini Veo não retornou operation id.');
        }

        return [
            'status' => 'Processando',
            'output' => 'Vídeo enviado diretamente ao Google Veo pelo Marketing IA.',
            'operation_id' => $operation,
            'metadata' => [
                'adapter_ready' => true,
                'adapter' => 'google_veo_predict_long_running',
                'model' => $model,
                'aspect_ratio' => $aspectRatio,
                'resolution' => $resolution,
                'duration_seconds' => $duration,
                'flow_dependency' => false,
                'prompt_length' => mb_strlen($prompt),
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
