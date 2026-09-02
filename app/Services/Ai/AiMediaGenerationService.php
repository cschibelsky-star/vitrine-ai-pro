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
            $message = $e->getMessage();
            $quotaUnavailable = str_contains(strtolower($message), 'quota')
                || str_contains($message, 'HTTP 429')
                || str_contains(strtolower($message), 'too_many_requests');

            $generation->update([
                'status' => $quotaUnavailable ? 'Indisponível' : 'Erro',
                'error_message' => $message,
                'output' => $message,
                'metadata' => array_merge((array) $generation->metadata, [
                    'failure_type' => $quotaUnavailable ? 'quota_or_billing' : 'provider_error',
                    'retryable' => ! $quotaUnavailable,
                ]),
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
                    'mime_type' => 'image/jpeg',
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
