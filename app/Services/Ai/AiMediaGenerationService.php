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

        if ($capability === 'image_generation' && in_array($providerSlug, ['gemini', 'google', 'google-gemini'], true)) {
            return $this->generateGoogleImage($provider, $prompt, $model);
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

        $binary = $this->applyOfficialLogoToImage($binary);
        $mimeType = 'image/png';

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
                'branding' => 'official_logo_top_right',
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
