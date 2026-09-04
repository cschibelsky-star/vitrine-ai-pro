<?php

namespace App\Filament\Pages;

use App\Marketing\Application\MarketingDashboardStateReader;
use App\Marketing\Domain\Agents\AgentRegistry;
use Filament\Pages\Page;

class MarketingDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    protected static ?string $navigationGroup = '10 · IA Center';
    protected static ?string $navigationLabel = 'Marketing IA';
    protected static ?string $title = 'Marketing IA';
    protected static ?int $navigationSort = 2;
    protected static string $view = 'filament.pages.marketing-dashboard';

    public function getAgents(): array
    {
        return app(AgentRegistry::class)->all();
    }

    public function getRuntime(): array
    {
        $gemini = (array) config('marketing_agents.gemini', []);

        return [
            'approval_mode' => (string) config('marketing_agents.approval_mode', 'unknown'),
            'schema_version' => (string) config('marketing_agents.schema_version', 'unknown'),
            'gemini_configured' => filled($gemini['api_key'] ?? null),
            'strategy_enabled' => (bool) ($gemini['strategy_enabled'] ?? false),
            'model' => (string) ($gemini['model'] ?? 'not configured'),
        ];
    }

    public function getCampaignState(): array
    {
        return app(MarketingDashboardStateReader::class)->latest();
    }

    public function getPipeline(): array
    {
        return [
            ['label' => 'Estratégia', 'agents' => ['product_market_strategist']],
            ['label' => 'Planejamento', 'agents' => ['campaign_planner']],
            ['label' => 'Copy', 'agents' => ['copy_content']],
            ['label' => 'Criação', 'agents' => ['creative_director', 'video_producer']],
            ['label' => 'Distribuição', 'agents' => ['social_distribution']],
            ['label' => 'QA', 'agents' => ['qa_brand_guardian']],
        ];
    }
}
