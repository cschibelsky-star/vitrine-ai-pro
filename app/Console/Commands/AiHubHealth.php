<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Shared\AI\Models\AiModel;
use App\Shared\AI\Models\AiProvider;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class AiHubHealth extends Command
{
    protected $signature = 'ai-hub:health';

    protected $description = 'Verifica schema, configuração e catálogo do Vitrine IA Hub sem executar chamadas externas.';

    public function handle(): int
    {
        $checks = [];

        $providersTable = Schema::hasTable('ai_providers');
        $modelsTable = Schema::hasTable('ai_models');
        $consumptionsTable = Schema::hasTable('ai_consumptions');
        $meteringReady = $consumptionsTable
            && Schema::hasColumn('ai_consumptions', 'provider_cost_brl')
            && Schema::hasColumn('ai_consumptions', 'ai_credits');
        $projectMeteringReady = $consumptionsTable
            && Schema::hasColumn('ai_consumptions', 'project_id');
        $billingReady = $modelsTable
            && Schema::hasColumn('ai_models', 'billing_unit')
            && Schema::hasColumn('ai_models', 'unit_cost_brl');
        $verificationReady = $modelsTable && Schema::hasColumn('ai_models', 'is_verified');
        $roteiaBaseUrl = rtrim((string) config('ai_hub.providers.roteia.base_url'), '/');
        $roteiaBaseUrlReady = $roteiaBaseUrl === 'https://api.roteia.ai/v1';
        $roteiaApiKeyReady = filled(config('ai_hub.providers.roteia.api_key'));

        $checks[] = ['Tabela ai_providers', $providersTable ? 'OK' : 'PENDENTE'];
        $checks[] = ['Tabela ai_models', $modelsTable ? 'OK' : 'PENDENTE'];
        $checks[] = ['Metering ampliado', $meteringReady ? 'OK' : 'PENDENTE'];
        $checks[] = ['Metering por projeto', $projectMeteringReady ? 'OK' : 'PENDENTE'];
        $checks[] = ['Billing multimodal', $billingReady ? 'OK' : 'PENDENTE'];
        $checks[] = ['Flag de verificação', $verificationReady ? 'OK' : 'PENDENTE'];
        $checks[] = ['ROTEIA_BASE_URL autorizada', $roteiaBaseUrlReady ? 'OK' : 'PENDENTE'];
        $checks[] = ['ROTEIA_API_KEY', $roteiaApiKeyReady ? 'OK' : 'PENDENTE'];

        $providerReady = false;
        if ($providersTable) {
            $provider = AiProvider::query()->where('slug', 'roteia')->first();
            $providerReady = $provider && in_array((string) $provider->status, ['active', 'experimental'], true);
            $checks[] = ['Provider Roteia', $providerReady ? 'OK' : 'PENDENTE'];
        }

        $activeModels = 0;
        $verifiedModels = 0;
        if ($modelsTable) {
            $activeModels = AiModel::query()->where('is_active', true)->count();
            if ($verificationReady) {
                $verifiedModels = AiModel::query()
                    ->where('is_active', true)
                    ->where('is_verified', true)
                    ->count();
            }
            $checks[] = ['Modelos ativos', (string) $activeModels];
            $checks[] = ['Modelos verificados', (string) $verifiedModels];
        }

        $this->table(['Verificação', 'Status'], $checks);

        $ready = $providersTable
            && $modelsTable
            && $meteringReady
            && $projectMeteringReady
            && $billingReady
            && $verificationReady
            && $roteiaBaseUrlReady
            && $roteiaApiKeyReady
            && $providerReady
            && $verifiedModels > 0;

        if ($ready) {
            $this->info('Vitrine IA Hub pronto para teste controlado de homologação.');
            return self::SUCCESS;
        }

        $this->warn('Vitrine IA Hub ainda possui pendências. Nenhuma chamada externa foi executada.');
        return self::INVALID;
    }
}
