<?php

declare(strict_types=1);

namespace App\Shared\AI\Services;

use App\Shared\AI\Models\AiModel;
use RuntimeException;

class AiModelRouter
{
    public function resolve(string $provider, string $profile = 'balanced', string $modality = 'text'): AiModel
    {
        $tiers = (array) data_get(config('ai_dev_hub.profiles'), $profile . '.tiers', []);

        if ($tiers === []) {
            $primary = (string) data_get(config('ai_dev_hub.profiles'), $profile . '.tier', 'balanced');
            $tiers = [$primary];
        }

        $tiers = array_values(array_unique(array_filter(array_map('strval', $tiers))));

        foreach ($tiers as $tier) {
            $model = $this->baseQuery($provider, $modality)
                ->where('tier', $tier)
                ->orderBy('input_cost_per_million')
                ->orderBy('output_cost_per_million')
                ->orderBy('id')
                ->first();

            if ($model) {
                return $model;
            }
        }

        throw new RuntimeException("Nenhum modelo homologado disponível para provider={$provider}, profile={$profile}, modality={$modality}.");
    }

    public function preview(string $provider, string $profile = 'balanced', string $modality = 'text'): array
    {
        $model = $this->resolve($provider, $profile, $modality);

        return [
            'provider' => $provider,
            'profile' => $profile,
            'modality' => $modality,
            'model' => $model->provider_model_id,
            'name' => $model->name,
            'tier' => $model->tier,
            'verified' => (bool) $model->is_verified,
            'experimental' => (bool) $model->is_experimental,
            'input_cost_per_million' => (float) $model->input_cost_per_million,
            'output_cost_per_million' => (float) $model->output_cost_per_million,
        ];
    }

    private function baseQuery(string $provider, string $modality)
    {
        return AiModel::query()
            ->whereHas('provider', fn ($query) => $query
                ->where('slug', $provider)
                ->whereIn('status', ['active', 'experimental', 'homologation', 'ativo', 'homologacao']))
            ->where('modality', $modality)
            ->where('is_active', true)
            ->where('is_verified', true);
    }
}
