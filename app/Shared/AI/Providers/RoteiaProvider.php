<?php

declare(strict_types=1);

namespace App\Shared\AI\Providers;

use App\Shared\AI\Models\AiProvider;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class RoteiaProvider
{
    private const ALLOWED_BASE_URL = 'https://api.roteia.ai/v1';

    public function chat(AiProvider $provider, string $model, array $messages, array $options = []): array
    {
        $fallback = (array) config('ai_hub.providers.roteia', []);
        $baseUrl = rtrim((string) ($fallback['base_url'] ?? self::ALLOWED_BASE_URL), '/');
        // Credencial de runtime tem prioridade; Provider Manager funciona apenas como fallback.
        // Isso evita que uma chave persistida antiga sobreponha ROTEIA_API_KEY válida do ambiente.
        $runtimeApiKey = trim((string) ($fallback['api_key'] ?? ''));
        $storedApiKey = trim((string) ($provider->api_key ?? ''));
        $apiKey = $runtimeApiKey !== '' ? $runtimeApiKey : $storedApiKey;
        $timeout = max(5, min(120, (int) ($fallback['timeout'] ?? 60)));

        $this->assertAllowedBaseUrl($baseUrl);

        if ($apiKey === '') {
            throw new RuntimeException('API Key da Roteia não cadastrada no Provider Manager.');
        }

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => $options['temperature'] ?? 0.3,
        ];

        foreach (['max_tokens', 'response_format', 'tools', 'tool_choice', 'thinking', 'reasoning_effort'] as $key) {
            if (array_key_exists($key, $options)) {
                $payload[$key] = $options[$key];
            }
        }

        $startedAt = microtime(true);
        $response = null;
        $maxAttempts = 3;
        $retryableStatuses = [429, 502, 503, 504];

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $response = $this->client($apiKey, $timeout)->post($baseUrl . '/chat/completions', $payload);

            if (! $response->failed() || ! in_array($response->status(), $retryableStatuses, true) || $attempt === $maxAttempts) {
                break;
            }

            usleep(250000 * $attempt);
        }

        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
        $requestId = $response?->header('x-request-id');

        if ($response === null || $response->failed()) {
            $status = $response?->status() ?? 0;
            throw new RuntimeException(
                'Roteia erro HTTP ' . $status
                . ($requestId ? ' [request_id=' . $requestId . ']' : '')
            );
        }

        $json = (array) $response->json();
        $content = (string) data_get($json, 'choices.0.message.content', '');
        $reasoningPresent = trim((string) data_get($json, 'choices.0.message.reasoning_content', '')) !== '';

        return [
            'content' => $content,
            'finish_reason' => data_get($json, 'choices.0.finish_reason'),
            'content_length' => mb_strlen($content),
            'reasoning_present' => $reasoningPresent,
            'input_tokens' => (int) data_get($json, 'usage.prompt_tokens', 0),
            'output_tokens' => (int) data_get($json, 'usage.completion_tokens', 0),
            'total_tokens' => (int) data_get($json, 'usage.total_tokens', 0),
            'request_id' => $requestId ?: data_get($json, 'id'),
            'duration_ms' => $durationMs,
            'provider_model' => $response->header('x-roteia-model'),
            'provider_lab' => $response->header('x-roteia-lab'),
            'provider_latency_ms' => $this->numericHeader($response->header('x-roteia-latency-ms')),
            'provider_cost_brl' => $this->numericHeader($response->header('x-roteia-cost-brl')),
        ];
    }

    public function testConnection(AiProvider $provider, string $model): array
    {
        return $this->chat($provider, $model, [
            ['role' => 'system', 'content' => 'Teste técnico de conexão do Vitrine IA Hub.'],
            ['role' => 'user', 'content' => 'Responda somente: CONEXAO_OK'],
        ], ['temperature' => 0]);
    }

    private function client(string $apiKey, int $timeout): PendingRequest
    {
        return Http::withToken($apiKey)
            ->acceptJson()
            ->asJson()
            ->timeout($timeout);
    }

    private function assertAllowedBaseUrl(string $baseUrl): void
    {
        if ($baseUrl !== self::ALLOWED_BASE_URL) {
            throw new RuntimeException('Base URL da Roteia não autorizada.');
        }
    }

    private function numericHeader(?string $value): ?float
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }
}
