<?php

namespace App\Filament\Pages;

use App\Shared\AI\Models\AiConsumption;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Schema;

class AiDevHubEnterprise extends Page
{
    /**
     * U3 Centro IA consolidado:
     * Hub IA Dev permanece disponível como ferramenta interna, mas não compete
     * com o Centro IA na navegação principal.
     */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = 'heroicon-o-code-bracket-square';
    protected static ?string $navigationGroup = '07 · IA Center';
    protected static ?string $navigationLabel = 'Hub IA Dev';
    protected static ?string $title = 'Hub IA Dev';
    protected static ?int $navigationSort = 90;
    protected static string $view = 'filament.pages.ai-dev-hub-enterprise';

    public function getViewData(): array
    {
        $ready = Schema::hasTable('ai_consumptions') && Schema::hasColumn('ai_consumptions', 'provider_cost_brl');

        $query = $ready
            ? AiConsumption::query()
                ->where('resource_type', 'like', 'internal_development.%')
                ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            : null;

        return [
            'enabled' => (bool) config('ai_dev_hub.enabled', false),
            'ready' => $ready,
            'defaultProvider' => (string) config('ai_dev_hub.default_provider', 'roteia'),
            'maxCompareModels' => (int) config('ai_dev_hub.max_compare_models', 3),
            'monthlyLimit' => (float) config('ai_dev_hub.monthly_limit_brl', 0),
            'requests' => $query ? (clone $query)->count() : 0,
            'cost' => $query ? (float) (clone $query)->sum('provider_cost_brl') : 0.0,
            'credits' => $query ? (float) (clone $query)->sum('ai_credits') : 0.0,
            'tokens' => $query ? (int) (clone $query)->sum('total_tokens') : 0,
        ];
    }
}
