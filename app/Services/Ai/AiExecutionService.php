<?php

namespace App\Services\Ai;

use App\Models\AiAgent;
use App\Models\AiExecution;
use App\Models\AiProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class AiExecutionService
{
    public function execute(AiAgent $agent, string $prompt): AiExecution
    {
        $agent = $agent->loadMissing('provider');
        $provider = $this->resolveProvider($agent);
        $providerType = $this->providerType($provider);
        $apiKey = $this->resolveApiKey($provider);
        $model = $this->resolveModel($agent, $provider, $providerType);

        $execution = AiExecution::create($this->safeData('ai_executions', [
            'name' => 'Execução - ' . $agent->name,
            'slug' => 'execucao-' . Str::slug($agent->name) . '-' . time(),
            'ai_agent_id' => $agent->id,
            'ai_provider_id' => $provider->id ?? null,
            'model_name' => $model,
            'status' => 'Processando',
            'input' => $prompt,
            'started_at' => now(),
        ]));

        $started = microtime(true);

        try {
            $output = $this->callProvider($providerType, $apiKey, $model, $prompt, $agent);
            $durationMs = (int) round((microtime(true) - $started) * 1000);

            $execution->update($this->safeData('ai_executions', [
                'status' => 'Concluído',
                'output' => $output,
                'duration_ms' => $durationMs,
                'finished_at' => now(),
            ], false));

            $this->registerConsumption($provider, $agent, $model, $prompt, $output, 'Concluído', $durationMs);
        } catch (Throwable $e) {
            $durationMs = (int) round((microtime(true) - $started) * 1000);

            $execution->update($this->safeData('ai_executions', [
                'status' => 'Erro',
                'output' => $e->getMessage(),
                'duration_ms' => $durationMs,
                'finished_at' => now(),
            ], false));

            $this->registerConsumption($provider, $agent, $model, $prompt, $e->getMessage(), 'Erro', $durationMs);
            $this->registerAlert($agent, $e->getMessage());
        }

        return $execution->refresh();
    }

    public function testProvider(AiProvider $provider): array
    {
        $providerType = $this->providerType($provider);
        $apiKey = $this->resolveApiKey($provider);
        $model = $this->resolveModel(null, $provider, $providerType);

        if (! $apiKey) {
            return [
                'ok' => false,
                'status' => 'Sem chave',
                'message' => 'API Key ausente no provedor e sem fallback no .env.',
            ];
        }

        try {
            $output = $this->callProvider(
                $providerType,
                $apiKey,
                $model,
                'Responda apenas: CONEXAO_OK',
                new AiAgent(['name' => 'Teste de Conexão', 'description' => 'Teste técnico de conexão.'])
            );

            return [
                'ok' => true,
                'status' => 'Conectado',
                'message' => trim($output),
            ];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'status' => 'Erro',
                'message' => $e->getMessage(),
            ];
        }
    }

    protected function resolveProvider(AiAgent $agent): ?AiProvider
    {
        if ($agent->relationLoaded('provider') && $agent->provider) {
            return $agent->provider;
        }

        if (! empty($agent->ai_provider_id)) {
            return AiProvider::find($agent->ai_provider_id);
        }

        return null;
    }

    protected function providerType(?AiProvider $provider): string
    {
        return strtolower((string) (
            $provider->provider_type
            ?? $provider->type
            ?? $provider->slug
            ?? 'manual'
        ));
    }

    protected function resolveApiKey(?AiProvider $provider): ?string
    {
        $providerType = $this->providerType($provider);
        $key = trim((string) ($provider->api_key ?? ''));

        if ($key !== '') {
            return $key;
        }

        return match ($providerType) {
            'openai' => env('OPENAI_API_KEY') ?: env('OPENAI_KEY') ?: null,
            'gemini', 'google', 'google-gemini' => env('GEMINI_API_KEY') ?: env('GOOGLE_API_KEY') ?: env('GOOGLE_GEMINI_API_KEY') ?: null,
            'heygen' => env('HEYGEN_API_KEY') ?: env('HEYGEN_KEY') ?: null,
            default => null,
        };
    }

    protected function resolveModel(?AiAgent $agent, ?AiProvider $provider, string $providerType): string
    {
        if ($agent && ! empty($agent->model_name)) {
            return $agent->model_name;
        }

        $config = $provider->config ?? [];
        if (is_string($config)) {
            $config = json_decode($config, true) ?: [];
        }

        if (! empty($config['model_default'])) {
            return $config['model_default'];
        }

        return match ($providerType) {
            'openai' => 'gpt-4o-mini',
            'gemini', 'google', 'google-gemini' => 'gemini-2.5-flash',
            default => 'manual',
        };
    }

    protected function callProvider(string $providerType, ?string $apiKey, string $model, string $prompt, AiAgent $agent): string
    {
        if (in_array($providerType, ['openai'], true)) {
            if (! $apiKey) {
                throw new \RuntimeException('API Key OpenAI ausente.');
            }

            $response = Http::withToken($apiKey)
                ->timeout(60)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => $agent->description ?: 'Você é um agente da Vitrine AI Pro.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.4,
                ]);

            if ($response->failed()) {
                throw new \RuntimeException('OpenAI erro: ' . $response->body());
            }

            return (string) data_get($response->json(), 'choices.0.message.content', 'Sem resposta da OpenAI.');
        }

        if (in_array($providerType, ['gemini', 'google', 'google-gemini'], true)) {
            if (! $apiKey) {
                throw new \RuntimeException('API Key Gemini ausente.');
            }

            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

            $response = Http::timeout(60)->post($url, [
                'contents' => [
                    ['parts' => [['text' => $prompt]]],
                ],
            ]);

            if ($response->failed()) {
                throw new \RuntimeException('Gemini erro: ' . $response->body());
            }

            return (string) data_get($response->json(), 'candidates.0.content.parts.0.text', 'Sem resposta do Gemini.');
        }

        if ($providerType === 'heygen') {
            return "HEYGEN CENTRALIZADO: provedor reconhecido. A execução de vídeo será implementada no módulo premium separado.";
        }

        return "EXECUÇÃO INTERNA\n\nAgente: {$agent->name}\nModelo: {$model}\n\nPrompt recebido:\n{$prompt}\n\nResultado: execução interna concluída. Configure OpenAI/Gemini para resposta externa real.";
    }

    protected function registerConsumption($provider, AiAgent $agent, string $model, string $input, string $output, string $status, int $durationMs = 0): void
    {
        if (! DB::getSchemaBuilder()->hasTable('ai_consumptions')) {
            return;
        }

        DB::table('ai_consumptions')->insert($this->safeData('ai_consumptions', [
            'name' => 'Consumo - ' . $agent->name,
            'slug' => 'consumo-' . Str::slug($agent->name) . '-' . time(),
            'ai_provider_id' => $provider->id ?? null,
            'ai_agent_id' => $agent->id,
            'model_name' => $model,
            'tokens' => max(1, intval((strlen($input) + strlen($output)) / 4)),
            'cost' => 0,
            'duration_ms' => $durationMs,
            'status' => $status,
            'description' => 'Registro automático de consumo da execução IA.',
        ]));
    }

    protected function registerAlert(AiAgent $agent, string $message): void
    {
        if (! DB::getSchemaBuilder()->hasTable('ai_alerts')) {
            return;
        }

        DB::table('ai_alerts')->insert($this->safeData('ai_alerts', [
            'name' => 'Falha na execução - ' . $agent->name,
            'title' => 'Falha na execução - ' . $agent->name,
            'slug' => 'falha-' . Str::slug($agent->name) . '-' . time(),
            'status' => 'Aberto',
            'level' => 'Erro',
            'message' => $message,
            'description' => 'Erro registrado automaticamente pelo executor IA.',
        ]));
    }

    protected function safeData(string $table, array $data, bool $withTimestamps = true): array
    {
        $cols = DB::getSchemaBuilder()->getColumnListing($table);
        $out = [];

        foreach ($data as $key => $value) {
            if (in_array($key, $cols, true)) {
                $out[$key] = $value;
            }
        }

        if ($withTimestamps && in_array('created_at', $cols, true) && ! isset($out['created_at'])) {
            $out['created_at'] = now();
        }

        if ($withTimestamps && in_array('updated_at', $cols, true) && ! isset($out['updated_at'])) {
            $out['updated_at'] = now();
        }

        return $out;
    }
}
