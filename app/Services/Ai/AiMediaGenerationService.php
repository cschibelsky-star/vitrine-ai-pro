<?php

namespace App\Services\Ai;

use App\Models\AiAgent;
use App\Models\AiMediaGeneration;
use App\Models\AiProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Shared\AI\Services\AiUsageTelemetry;
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
                'error_message' => $result['error_message'] ?? null,
                'metadata' => array_merge((array) $generation->metadata, $result['metadata'] ?? []),
                'duration_ms' => $durationMs,
                'finished_at' => in_array($result['status'] ?? null, ['Concluído', 'Erro'], true) ? now() : null,
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

        $started = microtime(true);
        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->timeout(120)
            ->post($baseUrl.'/'.ltrim($path, '/'), [
                'model' => $model,
                'prompt' => $prompt,
                'capability' => $capability,
            ]);

        $payload = (array) $response->json();
        $requestId = trim((string) ($payload['request_id'] ?? $response->header('x-request-id') ?? $payload['id'] ?? ''));
        $statusRaw = strtolower((string) ($payload['status'] ?? 'pending'));
        $operationId = (string) ($payload['operation_id'] ?? $payload['job_id'] ?? $payload['id'] ?? '');
        $result = [
            'status' => 'Pendente',
            'output' => 'Geração encaminhada à Roteia; entrega ainda não confirmada.',
            'operation_id' => $operationId,
            'asset_url' => $payload['asset_url'] ?? $payload['url'] ?? data_get($payload, 'data.0.url'),
            'metadata' => [
                'adapter_ready' => true,
                'adapter' => 'roteia_media',
                'provider_status' => $statusRaw,
                'provider_request_id' => $requestId !== '' ? $requestId : null,
                'http_status' => $response->status(),
                'model' => $model,
                'phase' => 'awaiting_asset',
                'generation_retry_allowed' => false,
            ],
        ];

        try {
            if (! $response->successful()) {
                throw new RuntimeException('Roteia mídia erro HTTP '.$response->status().'. Consulte o identificador da requisição.');
            }
            if (in_array($statusRaw, ['failed', 'error', 'cancelled', 'canceled'], true)) {
                throw new RuntimeException('A Roteia informou falha na geração de mídia.');
            }

            if ($capability === 'image_generation') {
                $base64 = data_get($payload, 'data.0.b64_json') ?? data_get($payload, 'output_image.data');
                if (is_string($base64) && $base64 !== '') {
                    $result = array_merge($result, $this->persistImage($base64));
                    $result['status'] = 'Concluído';
                    $result['output'] = 'Imagem recebida, validada e salva.';
                    $result['metadata']['phase'] = 'asset_saved';
                } elseif (in_array($statusRaw, ['completed', 'concluido', 'concluído', 'done', 'success'], true)
                    && empty($result['asset_url'])) {
                    throw new RuntimeException('Roteia informou conclusão, mas não retornou uma imagem utilizável. Nenhuma nova geração foi iniciada.');
                } elseif (! empty($result['asset_url'])) {
                    $result['metadata']['phase'] = 'download_pending';
                    $hosts = (array) data_get($provider->config, 'asset_hosts', []);
                    $result = array_merge($result, $this->downloadImage((string) $result['asset_url'], $hosts));
                    $result['status'] = 'Concluído';
                    $result['output'] = 'Imagem baixada, validada e salva.';
                    $result['metadata']['phase'] = 'asset_saved';
                }
            } elseif (in_array($statusRaw, ['completed', 'concluido', 'concluído', 'done', 'success'], true)) {
                // Outros tipos de mídia mantêm o contrato existente.
                $result['status'] = 'Concluído';
            }
        } catch (Throwable $e) {
            $result['status'] = 'Erro';
            $result['error_message'] = $e->getMessage();
            $result['output'] = $e->getMessage();
            $result['metadata']['phase'] = 'delivery_failed';
        }

        // O ID financeiro é distinto do ID de operação e deve sobreviver à falha de entrega.
        $payload['request_id'] = $requestId;
        $payload['id'] = $requestId;
        app(AiUsageTelemetry::class)->recordMedia(
            'roteia', $provider->id, null, 'core', (string) $model, $capability,
            $payload, (int) round((microtime(true) - $started) * 1000),
            match ($result['status']) {
                'Concluído' => 'completed',
                'Erro' => 'failed',
                default => 'pending',
            },
        );

        return $result;
    }


    protected function downloadImage(string $url, array $allowedHosts): array
    {
        $parts = parse_url($url);
        $host = strtolower((string) ($parts['host'] ?? ''));
        $allowedHosts = array_map('strtolower', array_filter($allowedHosts, 'is_string'));
        if (($parts['scheme'] ?? '') !== 'https' || $host === ''
            || isset($parts['user']) || isset($parts['pass'])
            || (isset($parts['port']) && $parts['port'] !== 443)
            || ! in_array($host, $allowedHosts, true)) {
            throw new RuntimeException('Download pendente: host HTTPS da imagem precisa estar autorizado em asset_hosts. Não gere novamente.');
        }

        $addresses = gethostbynamel($host);
        if (! $addresses || filter_var($host, FILTER_VALIDATE_IP)) {
            throw new RuntimeException('Host de imagem inválido para download seguro.');
        }
        foreach ($addresses as $address) {
            if (! filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw new RuntimeException('Download de imagem bloqueado: endereço não público.');
            }
        }
        if (! defined('CURLOPT_RESOLVE')) {
            throw new RuntimeException('Download seguro exige a extensão cURL.');
        }

        $temp = tmpfile();
        if ($temp === false) {
            throw new RuntimeException('Não foi possível preparar armazenamento temporário.');
        }
        $limit = 20 * 1024 * 1024;
        try {
            $response = Http::timeout(60)->connectTimeout(10)->withOptions([
                'allow_redirects' => false,
                'proxy' => '',
                'sink' => $temp,
                // Fixar o IP já validado impede troca de DNS entre checagem e conexão.
                'curl' => [CURLOPT_RESOLVE => [$host.':443:'.$addresses[0]]],
                'on_headers' => static function ($response) use ($limit): void {
                    if ((int) $response->getHeaderLine('Content-Length') > $limit) {
                        throw new RuntimeException('Imagem excede o limite de 20 MB.');
                    }
                },
                'progress' => static function ($total, $downloaded) use ($limit): void {
                    if ($downloaded > $limit) {
                        throw new RuntimeException('Imagem excede o limite de 20 MB.');
                    }
                },
            ])->get($url);
            if ($response->status() !== 200) {
                throw new RuntimeException('Falha no download da imagem HTTP '.$response->status().'. Não gere novamente.');
            }
            rewind($temp);
            $binary = stream_get_contents($temp, $limit + 1);
            if ($binary === false || strlen($binary) > $limit) {
                throw new RuntimeException('Imagem inválida ou maior que 20 MB.');
            }

            return $this->persistImage(base64_encode($binary));
        } finally {
            if (is_resource($temp)) {
                fclose($temp);
            }
        }
    }

    protected function persistImage(string $base64): array
    {
        // Limite aplicado antes da decodificação para evitar alocação sem limite.
        if (strlen($base64) > 28 * 1024 * 1024) {
            throw new RuntimeException('Imagem excede o limite de armazenamento de 20 MB.');
        }

        $binary = base64_decode($base64, true);
        $info = $binary !== false && $binary !== '' ? @getimagesizefromstring($binary) : false;
        $mime = is_array($info) ? ($info['mime'] ?? '') : '';
        $extension = match ($mime) {
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
            default => null,
        };
        if ($extension === null || strlen($binary) > 20 * 1024 * 1024) {
            throw new RuntimeException('O provedor retornou dados de imagem inválidos ou não suportados.');
        }

        $disk = config('filesystems.default', 'local');
        $path = 'ai-generated/marketing/'.now()->format('Y/m/d').'/'.Str::uuid().'.'.$extension;
        if (! Storage::disk($disk)->put($path, $binary) || ! Storage::disk($disk)->exists($path)) {
            throw new RuntimeException('Falha ao salvar a imagem recebida. Não gere novamente.');
        }

        return ['asset_path' => $path, 'asset_url' => null];
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
