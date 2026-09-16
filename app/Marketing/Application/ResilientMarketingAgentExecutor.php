<?php

declare(strict_types=1);

namespace App\Marketing\Application;

use Illuminate\Support\Facades\Http;
use JsonException;
use RuntimeException;
use Throwable;

final class ResilientMarketingAgentExecutor implements MarketingAgentExecutor
{
    /** @var array<string, array<string, mixed>> */
    private array $metadata = [];

    public function __construct(
        private readonly GeminiStrategyAgent $geminiStrategy,
        private readonly SimulatedMarketingAgentExecutor $simulated,
        private readonly SchemaContractValidator $validator,
    ) {
    }

    public function execute(string $agentId, array $campaign, array $inputs): array
    {
        if ($agentId === 'video_producer') {
            $this->metadata[$agentId] = [
                'provider' => 'video-engine-plan',
                'fallback' => false,
                'generation_mode' => 'explicit',
            ];

            return $this->simulated->execute($agentId, $campaign, $inputs);
        }

        if (! $this->liveHubEnabled()) {
            $this->metadata[$agentId] = [
                'provider' => 'simulated',
                'fallback' => false,
                'live_disabled' => true,
            ];

            return $this->simulated->execute($agentId, $campaign, $inputs);
        }

        try {
            if ($agentId === 'product_market_strategist') {
                $output = $this->geminiStrategy->execute($campaign);
                $this->metadata[$agentId] = $this->geminiStrategy->metadata();

                return $output;
            }

            return $this->executeViaHub($agentId, $campaign, $inputs);
        } catch (Throwable $exception) {
            report($exception);

            $message = preg_replace('/Bearer\s+[A-Za-z0-9._~-]+/i', 'Bearer [redacted]', $exception->getMessage()) ?: 'Hub execution failed.';

            $this->metadata[$agentId] = [
                'provider' => 'simulated',
                'fallback' => true,
                'fallback_reason' => $exception::class,
                'fallback_message' => mb_substr($message, 0, 240),
            ];

            return $this->simulated->execute($agentId, $campaign, $inputs);
        }
    }

    public function metadataFor(string $agentId): array
    {
        return $this->metadata[$agentId] ?? [];
    }

    private function executeViaHub(string $agentId, array $campaign, array $inputs): array
    {
        $schemaName = $this->schemaFor($agentId);
        $schemaPath = base_path("resources/schemas/marketing/{$schemaName}.schema.json");
        $schemaJson = (string) file_get_contents($schemaPath);

        if ($schemaJson === '') {
            throw new RuntimeException("Marketing schema [{$schemaName}] is unavailable.");
        }

        $url = trim((string) config('marketing_agents.hub.url'));
        $token = trim((string) config('marketing_agents.hub.token'));
        $projectId = trim((string) config('marketing_agents.hub.project_id'));
        $capability = trim((string) config('marketing_agents.hub.capability', 'marketing_generation'));
        $timeout = (int) config('marketing_agents.hub.timeout', 60);
        $startedAt = hrtime(true);

        $context = json_encode([
            'agent_id' => $agentId,
            'campaign' => $campaign,
            'upstream_outputs' => $inputs,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $response = Http::acceptJson()
            ->asJson()
            ->withToken($token)
            ->withHeaders(['X-Vitrine-Project' => $projectId])
            ->timeout(max(1, min($timeout, 120)))
            ->retry(2, 250, throw: false)
            ->post($url, [
                'project_id' => $projectId,
                'capability' => $capability,
                'input' => [
                    'system' => "Você é o agente {$agentId} do Marketing IA da Vitrine IA Pro. Use somente os fatos e saídas anteriores fornecidos. Não invente preços, clientes, depoimentos, métricas ou funcionalidades. Retorne somente JSON válido e exatamente compatível com este JSON Schema: {$schemaJson}",
                    'user' => $context,
                    'response_format' => 'json',
                    'temperature' => 0.2,
                ],
            ])
            ->throw();

        if (! $response->json('ok')) {
            throw new RuntimeException("Marketing Hub returned an unsuccessful execution for [{$agentId}].");
        }

        $text = $response->json('output_text');
        if (! is_string($text) || trim($text) === '') {
            throw new RuntimeException("Marketing Hub returned no output for [{$agentId}].");
        }

        $text = preg_replace('/^```(?:json)?\\s*/i', '', trim($text)) ?? trim($text);
        $text = preg_replace('/\\s*```$/', '', $text) ?? $text;

        try {
            $output = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException("Marketing Hub returned invalid JSON for [{$agentId}].", 0, $exception);
        }

        if (! is_array($output)) {
            throw new RuntimeException("Marketing Hub output for [{$agentId}] must be an object.");
        }

        $this->validator->assertValid($schemaName, $output);
        $this->metadata[$agentId] = [
            'provider' => 'centro-ia',
            'model' => $response->json('model') ?: 'hub-routed',
            'execution_id' => $response->json('execution_id'),
            'duration_ms' => (int) round((hrtime(true) - $startedAt) / 1_000_000),
            'fallback' => false,
        ];

        return $output;
    }

    private function schemaFor(string $agentId): string
    {
        return match ($agentId) {
            'campaign_planner' => 'campaign-plan',
            'copy_content' => 'content-package',
            'creative_director' => 'creative-package',
            'social_distribution' => 'distribution-plan',
            'qa_brand_guardian' => 'qa-report',
            'performance_analyst' => 'performance-report',
            default => throw new RuntimeException("No live Marketing Hub schema configured for [{$agentId}]."),
        };
    }

    private function liveHubEnabled(): bool
    {
        return (bool) config('marketing_agents.hub.strategy_enabled', false)
            && trim((string) config('marketing_agents.hub.url')) !== ''
            && trim((string) config('marketing_agents.hub.token')) !== ''
            && trim((string) config('marketing_agents.hub.project_id')) !== '';
    }
}
