<?php

namespace App\Services\Ai;

use App\Models\AiAgent;
use App\Models\AiMediaGeneration;
use App\Models\AiProvider;
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
                'phase' => 'adapter_pending',
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
}
