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
        $apiKey = (string) config('marketing_agents.gemini.api_key');
        $model = (string) config('marketing_agents.gemini.model');
        $timeout = (int) config('marketing_agents.gemini.timeout', 60);

        if ($apiKey === '' || $model === '') {
            throw new RuntimeException('Gemini strategy agent is not configured.');
        }

        if (! preg_match('/^[A-Za-z0-9._-]+$/', $model)) {
            throw new RuntimeException('Gemini model name is invalid.');
        }

        $schema = $this->schema();
        $startedAt = hrtime(true);

        try {
            $response = Http::baseUrl('https://generativelanguage.googleapis.com')
                ->withHeaders(['x-goog-api-key' => $apiKey])
                ->acceptJson()
                ->asJson()
                ->timeout(max(1, min($timeout, 120)))
                ->retry(2, 250, throw: false)
                ->post("/v1beta/models/{$model}:generateContent", [
                    'contents' => [[
                        'role' => 'user',
                        'parts' => [['text' => $this->prompt($campaign)]],
                    ]],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                        'responseSchema' => $this->toGeminiSchema($schema),
                        'temperature' => 0.2,
                    ],
                ])
                ->throw();
        } catch (ConnectionException|RequestException $exception) {
            throw new RuntimeException('Gemini strategy request failed.', 0, $exception);
        }

        $text = $response->json('candidates.0.content.parts.0.text');

        if (! is_string($text) || trim($text) === '') {
            throw new RuntimeException('Gemini returned no structured strategy.');
        }

        try {
            $strategy = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Gemini returned invalid JSON.', 0, $exception);
        }

        if (! is_array($strategy)) {
            throw new RuntimeException('Gemini strategy response must be an object.');
        }

        $strategy['strategy_id'] = 'STRATEGY-'.(string) $campaign['campaign_id'];
        $strategy['campaign_id'] = (string) $campaign['campaign_id'];
        $strategy['status'] = 'completed';

        $usage = (array) $response->json('usageMetadata', []);
        $this->lastMetadata = [
            'provider' => 'gemini',
            'model' => $model,
            'duration_ms' => (int) round((hrtime(true) - $startedAt) / 1_000_000),
            'prompt_tokens' => (int) ($usage['promptTokenCount'] ?? 0),
            'output_tokens' => (int) ($usage['candidatesTokenCount'] ?? 0),
            'total_tokens' => (int) ($usage['totalTokenCount'] ?? 0),
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
Você é o Product & Market Strategist da Vitrine IA Pro.
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

    /** @param array<string, mixed> $schema @return array<string, mixed> */
    private function toGeminiSchema(array $schema): array
    {
        $allowed = [
            'type', 'format', 'description', 'nullable', 'enum',
            'items', 'properties', 'required', 'minItems', 'maxItems',
            'minimum', 'maximum', 'minLength', 'maxLength',
        ];
        $result = array_intersect_key($schema, array_flip($allowed));

        if (array_key_exists('const', $schema)) {
            $result['type'] = get_debug_type($schema['const']);
            $result['type'] = $result['type'] === 'int' ? 'integer' : $result['type'];
            $result['enum'] = [$schema['const']];
        }

        if (isset($result['properties']) && is_array($result['properties'])) {
            $result['properties'] = array_map(
                fn (array $property): array => $this->toGeminiSchema($property),
                $result['properties'],
            );
        }

        if (isset($result['items']) && is_array($result['items'])) {
            $result['items'] = $this->toGeminiSchema($result['items']);
        }

        return $result;
    }
}
