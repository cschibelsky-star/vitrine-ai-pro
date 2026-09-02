<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Shared\AI\Models\AiModel;
use App\Shared\AI\Services\AiHubService;
use Illuminate\Console\Command;
use Throwable;

class AiHubTest extends Command
{
    protected $signature = 'ai-hub:test {--provider=roteia} {--model=}';

    protected $description = 'Executa uma chamada curta de homologação no Vitrine IA Hub e mostra metering/custo.';

    public function handle(AiHubService $hub): int
    {
        $provider = (string) $this->option('provider');
        $model = (string) ($this->option('model') ?: AiModel::query()
            ->whereHas('provider', fn ($query) => $query->where('slug', $provider))
            ->where('is_active', true)
            ->where('is_verified', true)
            ->orderByRaw("FIELD(tier, 'economy', 'balanced', 'premium')")
            ->value('provider_model_id'));

        if ($model === '') {
            $this->error('Nenhum modelo ativo e verificado encontrado para o provider ' . $provider . '. Homologue ao menos um modelo antes do teste.');
            return self::FAILURE;
        }

        $this->info("Testando provider={$provider} model={$model}");

        try {
            $result = $hub->generate([
                'provider' => $provider,
                'model' => $model,
                'resource_type' => 'hub.homologation',
                'system' => 'Você está executando um teste técnico de homologação do Vitrine IA Hub.',
                'prompt' => 'Responda somente: HUB_OK',
                'options' => ['temperature' => 0],
            ]);

            $this->line('Resposta: ' . ($result['content'] ?? ''));
            $this->table(
                ['Provider', 'Modelo', 'Entrada', 'Saída', 'Total', 'Custo', 'Créditos', 'ms'],
                [[
                    $result['provider'] ?? '',
                    $result['model'] ?? '',
                    data_get($result, 'usage.input_tokens', 0),
                    data_get($result, 'usage.output_tokens', 0),
                    data_get($result, 'usage.total_tokens', 0),
                    data_get($result, 'usage.provider_cost_brl', 0),
                    data_get($result, 'usage.ai_credits', 0),
                    $result['duration_ms'] ?? 0,
                ]]
            );

            return trim((string) ($result['content'] ?? '')) === 'HUB_OK' ? self::SUCCESS : self::INVALID;
        } catch (Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }
}
