<?php

declare(strict_types=1);

namespace App\Marketing\Application;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use JsonException;
use RuntimeException;

final class GeminiStrategyAgent
{
    /** @var array<string, mixed> */
    private array $lastMetadata = [];

    /** @param array<string, mixed> $campaign @return array<string, mixed> */
    public function execute(array $campaign): array
    {
        $url = trim((string) config('marketing_agents.hub.url'));
        $token = trim((string) config('marketing_agents.hub.token'));
        $projectId = trim((string) config('marketing_agents.hub.project_id', 'vitrine-marketing-agents-core'));
        $capability = trim((string) config('marketing_agents.hub.capability', 'marketing_generation'));
        $timeout = (int) config('marketing_agents.hub.timeout', 60);

        if ($url === '' || $token === '' || $projectId === '' || $capability === '') {
            throw new RuntimeException('Marketing Hub strategy agent is not configured.');
        }

        $schema = $this->schema();
        $schemaJson = json_encode($schema, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $startedAt = hrtime(true);

        try {
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
                        'system' => 'Você é o Product & Market Strategist da Vitrine IA Pro. Retorne somente JSON válido compatível com este schema: '.$schemaJson,
                        'user' => $this->prompt($campaign),
                        'response_format' => 'json',
                        'temperature' => 0.2,
                    ],
                ])
                ->throw();
        } catch (ConnectionException|RequestException $exception) {
            throw new RuntimeException('Marketing Hub strategy request failed.', 0, $exception);
        }

        if (! $response->json('ok')) {
            throw new RuntimeException('Marketing Hub returned an unsuccessful execution.');
        }

        $text = $response->json('output_text');
        if (! is_string($text) || trim($text) === '') {
            throw new RuntimeException('Marketing Hub returned no structured strategy.');
        }

        $text = preg_replace('/^```(?:json)?\s*/i', '', trim($text)) ?? trim($text);
        $text = preg_replace('/\s*```$/', '', $text) ?? $text;

        try {
            $strategy = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Marketing Hub returned invalid JSON.', 0, $exception);
        }

        if (! is_array($strategy)) {
            throw new RuntimeException('Marketing Hub strategy response must be an object.');
        }

        $strategy['strategy_id'] = 'STRATEGY-'.(string) $campaign['campaign_id'];
        $strategy['campaign_id'] = (string) $campaign['campaign_id'];
        $strategy['status'] = 'completed';

        $this->lastMetadata = [
            'provider' => 'centro-ia',
            'model' => $response->json('model') ?: 'hub-routed',
            'execution_id' => $response->json('execution_id'),
            'duration_ms' => (int) round((hrtime(true) - $startedAt) / 1_000_000),
            'fallback' => false,
        ];

        return $strategy;
    }

    /** @return array<string, mixed> */
    public function metadata(): array
    {
        return $this->lastMetadata;
    }

    /** @return array<string, mixed> */
    private function schema(): array
    {
        $path = base_path('resources/schemas/marketing/strategy-output.schema.json');

        try {
            return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Strategy schema is invalid.', 0, $exception);
        }
    }

    /** @param array<string, mixed> $campaign */
    private function prompt(array $campaign): string
    {
        $facts = json_encode($campaign['known_facts'] ?? [], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $restrictions = json_encode($campaign['restrictions'] ?? [], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
Crie a estratégia do produto descrito na campanha abaixo em português do Brasil.
Use exclusivamente os fatos conhecidos fornecidos.
Não invente preços, clientes, depoimentos, métricas, integrações ou funcionalidades.
Registre qualquer lacuna em open_questions e qualquer hipótese em assumptions.
Retorne apenas o JSON compatível com o schema solicitado.

Campanha: {$campaign['name']}
Objetivo: {$campaign['objective']}
Fatos conhecidos: {$facts}
Restrições: {$restrictions}
PROMPT;
    }
}
