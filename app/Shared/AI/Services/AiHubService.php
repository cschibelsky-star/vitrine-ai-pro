<?php

declare(strict_types=1);

namespace App\Shared\AI\Services;

use App\Shared\AI\Models\AiConsumption;
use App\Shared\AI\Models\AiModel;
use App\Shared\AI\Models\AiProvider;
use App\Shared\AI\Providers\RoteiaProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class AiHubService
{
    public function __construct(private readonly RoteiaProvider $roteia)
    {
    }

    public function generate(array $request): array
    {
        $providerSlug = (string) ($request['provider'] ?? config('ai_hub.default_provider', 'roteia'));
        $modelId = (string) ($request['model'] ?? '');

        if ($modelId === '') {
            throw new RuntimeException('Modelo de IA não informado.');
        }

        $provider = AiProvider::query()->where('slug', $providerSlug)->first();
        if (! $provider) {
            throw new RuntimeException("Provider {$providerSlug} não cadastrado no Centro IA.");
        }

        if (! in_array((string) $provider->status, ['active', 'experimental', 'homologation', 'ativo', 'homologacao'], true)) {
            throw new RuntimeException("Provider {$providerSlug} não está habilitado para uso.");
        }

        $this->assertProviderScope($provider, $request);

        $model = AiModel::query()
            ->where('ai_provider_id', $provider->id)
            ->where('provider_model_id', $modelId)
            ->first();

        if (! $model) {
            throw new RuntimeException("Modelo {$modelId} não cadastrado para o provider {$providerSlug}.");
        }
        if (! $model->is_active) {
            throw new RuntimeException("Modelo {$modelId} está inativo.");
        }
        if (! $model->is_verified) {
            throw new RuntimeException("Modelo {$modelId} ainda não foi verificado para uso.");
        }
        if ((string) $model->modality !== 'text') {
            throw new RuntimeException("Modelo {$modelId} não é compatível com geração de texto/chat.");
        }

        $messages = $request['messages'] ?? null;
        if (! is_array($messages) || $messages === []) {
            $messages = [
                ['role' => 'system', 'content' => (string) ($request['system'] ?? 'Você é um agente da Vitrine IA Pro.')],
                ['role' => 'user', 'content' => (string) ($request['prompt'] ?? '')],
            ];
        }

        $startedAt = microtime(true);

        try {
            $providerType = strtolower((string) ($provider->provider_type ?? ''));
            $result = match (true) {
                $providerSlug === 'roteia' || $providerType === 'roteia' => $this->roteia->chat($provider, $modelId, $messages, (array) ($request['options'] ?? [])),
                $providerType === 'openai' => $this->callOpenAi($provider, $modelId, $messages, (array) ($request['options'] ?? [])),
                default => throw new RuntimeException("Provider {$providerSlug} ({$providerType}) ainda não possui driver no AI Hub."),
            };

            $durationMs = (int) ($result['duration_ms'] ?? round((microtime(true) - $startedAt) * 1000));
            $calculatedCost = $this->calculateCost($model, (int) ($result['input_tokens'] ?? 0), (int) ($result['output_tokens'] ?? 0));
            $providerCost = is_numeric($result['provider_cost_brl'] ?? null) ? (float) $result['provider_cost_brl'] : $calculatedCost;
            $billable = $providerCost * (float) config('ai_hub.credit.markup_multiplier', 1.0);
            $creditValue = max(0.000001, (float) config('ai_hub.credit.brl_per_credit', 0.01));
            $credits = $billable / $creditValue;

            try {
                $this->recordConsumption($provider, $model, $request, $result, $providerCost, $billable, $credits, 'Concluído', $durationMs);
            } catch (Throwable $meteringError) {
                report($meteringError);
            }

            return [
                'provider' => $providerSlug,
                'model' => $modelId,
                'content' => $result['content'] ?? '',
                'response_meta' => [
                    'finish_reason' => $result['finish_reason'] ?? null,
                    'content_length' => (int) ($result['content_length'] ?? mb_strlen((string) ($result['content'] ?? ''))),
                    'reasoning_present' => (bool) ($result['reasoning_present'] ?? false),
                ],
                'usage' => [
                    'input_tokens' => (int) ($result['input_tokens'] ?? 0),
                    'output_tokens' => (int) ($result['output_tokens'] ?? 0),
                    'total_tokens' => (int) ($result['total_tokens'] ?? 0),
                    'provider_cost_brl' => round($providerCost, 6),
                    'billable_cost_brl' => round($billable, 6),
                    'ai_credits' => round($credits, 4),
                ],
                'duration_ms' => $durationMs,
                'request_id' => $result['request_id'] ?? null,
            ];
        } catch (Throwable $e) {
            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
            try {
                $this->recordConsumption($provider, $model, $request, [], 0, 0, 0, 'Erro', $durationMs, $e->getMessage());
            } catch (Throwable $meteringError) {
                report($meteringError);
            }
            throw $e;
        }
    }

    private function assertProviderScope(AiProvider $provider, array $request): void
    {
        if (strtolower((string) ($provider->provider_type ?? '')) !== 'heygen') {
            return;
        }

        $projectId = (string) ($request['project_id'] ?? '');
        $allowedProjects = ['tvsumare', 'cursos-ia-mvp'];

        if (! in_array($projectId, $allowedProjects, true)) {
            throw new RuntimeException('HeyGen é exclusivo da TV Sumaré e do gerador de vídeos do Cursos IA.');
        }
    }

    private function callOpenAi(AiProvider $provider, string $modelId, array $messages, array $options): array
    {
        $config = (array) ($provider->config ?? []);
        $baseUrl = rtrim((string) ($config['base_url'] ?? 'https://api.openai.com/v1'), '/');
        $apiKey = trim((string) ($provider->api_key ?? ''));
        $timeout = max(5, min(120, (int) ($config['timeout'] ?? 60)));

        if ($baseUrl !== 'https://api.openai.com/v1') {
            throw new RuntimeException('Base URL OpenAI não autorizada no AI Hub.');
        }
        if ($apiKey === '') {
            throw new RuntimeException('API Key OpenAI não cadastrada no Provider Manager.');
        }

        $payload = [
            'model' => $modelId,
            'messages' => $messages,
            'temperature' => $options['temperature'] ?? 0.3,
        ];

        if (($options['response_format'] ?? null) === 'json') {
            $payload['response_format'] = ['type' => 'json_object'];
        }
        if (array_key_exists('max_tokens', $options)) {
            $payload['max_tokens'] = $options['max_tokens'];
        }

        $startedAt = microtime(true);
        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->asJson()
            ->timeout($timeout)
            ->post($baseUrl . '/chat/completions', $payload);
        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
        $requestId = $response->header('x-request-id');

        if ($response->failed()) {
            $errorCode = (string) data_get($response->json(), 'error.code', '');
            $errorType = (string) data_get($response->json(), 'error.type', '');
            throw new RuntimeException('OpenAI erro HTTP ' . $response->status()
                . ($errorCode !== '' ? ' code=' . $errorCode : '')
                . ($errorType !== '' ? ' type=' . $errorType : '')
                . ($requestId ? ' [request_id=' . $requestId . ']' : ''));
        }

        $json = (array) $response->json();
        $content = (string) data_get($json, 'choices.0.message.content', '');

        return [
            'content' => $content,
            'finish_reason' => data_get($json, 'choices.0.finish_reason'),
            'content_length' => mb_strlen($content),
            'reasoning_present' => false,
            'input_tokens' => (int) data_get($json, 'usage.prompt_tokens', 0),
            'output_tokens' => (int) data_get($json, 'usage.completion_tokens', 0),
            'total_tokens' => (int) data_get($json, 'usage.total_tokens', 0),
            'request_id' => $requestId ?: data_get($json, 'id'),
            'duration_ms' => $durationMs,
            'provider_model' => data_get($json, 'model', $modelId),
            'provider_lab' => 'openai',
            'provider_latency_ms' => null,
            'provider_cost_brl' => null,
        ];
    }

    private function calculateCost(AiModel $model, int $inputTokens, int $outputTokens): float
    {
        return ((float) $model->input_cost_per_million / 1_000_000) * $inputTokens
            + ((float) $model->output_cost_per_million / 1_000_000) * $outputTokens;
    }

    private function recordConsumption(AiProvider $provider, AiModel $model, array $request, array $result, float $providerCost, float $billableCost, float $credits, string $status, int $durationMs, ?string $error = null): void
    {
        $resourceType = (string) ($request['resource_type'] ?? 'text.generate');
        $usageScope = str_starts_with($resourceType, 'internal_development.') ? 'internal_development' : 'product';
        $auditMetadata = array_intersect_key((array) ($request['audit_metadata'] ?? []), array_flip([
            'mission_id',
            'evidence_pack_version',
            'evidence_pack_sha256',
            'runtime_version',
            'grounding_policy',
            'observer_mode',
            'domain',
            'target_project_id',
        ]));

        AiConsumption::create([
            'company_id' => $request['company_id'] ?? null,
            'product_id' => $request['product_id'] ?? null,
            'license_id' => $request['license_id'] ?? null,
            'ai_agent_id' => $request['ai_agent_id'] ?? null,
            'project_id' => $request['project_id'] ?? null,
            'ai_provider_id' => $provider->id,
            'model_name' => $model->provider_model_id,
            'resource_type' => $resourceType,
            'quantity' => 1,
            'estimated_cost' => $providerCost,
            'provider_cost_brl' => $providerCost,
            'billable_cost_brl' => $billableCost,
            'ai_credits' => $credits,
            'input_tokens' => (int) ($result['input_tokens'] ?? 0),
            'output_tokens' => (int) ($result['output_tokens'] ?? 0),
            'total_tokens' => (int) ($result['total_tokens'] ?? 0),
            'duration_ms' => $durationMs,
            'status' => $status,
            'request_id' => $result['request_id'] ?? Str::uuid()->toString(),
            'consumption_date' => now()->toDateString(),
            'metadata' => [
                'mode' => (string) data_get($provider->config, 'mode', 'experimental'),
                'usage_scope' => $usageScope,
                'project_id' => $request['project_id'] ?? null,
                'model_id' => $model->id,
                'model_verified' => (bool) $model->is_verified,
                'provider_model' => $result['provider_model'] ?? null,
                'provider_lab' => $result['provider_lab'] ?? null,
                'provider_latency_ms' => $result['provider_latency_ms'] ?? null,
                'finish_reason' => $result['finish_reason'] ?? null,
                'content_length' => $result['content_length'] ?? null,
                'reasoning_present' => $result['reasoning_present'] ?? null,
                'audit' => $auditMetadata,
                'error' => $error,
            ],
            'notes' => $error,
        ]);
    }
}
