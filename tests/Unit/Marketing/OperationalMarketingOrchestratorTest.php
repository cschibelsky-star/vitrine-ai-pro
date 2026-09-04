<?php

namespace Tests\Unit\Marketing;

use App\Marketing\Application\MarketingOrchestrator;
use App\Marketing\Application\SchemaContractValidator;
use App\Marketing\Application\SimulatedMarketingAgentExecutor;
use Tests\TestCase;

class OperationalMarketingOrchestratorTest extends TestCase
{
    public function test_operational_orchestrator_runs_campaign_state_end_to_end(): void
    {
        $result = app(MarketingOrchestrator::class)->runOperationalCampaign(
            $this->campaign(),
            app(SimulatedMarketingAgentExecutor::class),
            app(SchemaContractValidator::class),
        );

        $this->assertSame('operational', $result['mode']);
        $this->assertSame('completed', $result['status']);
        $this->assertSame('approved', $result['qa_result']);
        $this->assertFalse($result['published']);
        $this->assertFalse($result['spent']);
        $this->assertCount(7, $result['artifacts']);
        $this->assertContains(
            ['creative_director', 'video_producer'],
            $result['execution_batches'],
        );

        foreach ($result['state']['tasks'] as $task) {
            $this->assertSame('completed', $task['status']);
        }
    }

    public function test_operational_state_starts_only_strategy_and_excludes_disabled_agents(): void
    {
        $state = app(MarketingOrchestrator::class)->createOperationalCampaign($this->campaign());

        $this->assertSame('ready', $state->status()->value);
        $this->assertSame('ready', $state->task('product_market_strategist')['status']->value);
        $this->assertArrayNotHasKey('marketing_director', $state->tasks());
        $this->assertArrayNotHasKey('performance_analyst', $state->tasks());
    }

    /** @return array<string, mixed> */
    private function campaign(): array
    {
        return [
            'campaign_id' => 'CAM-ORCH-001',
            'tenant_id' => 1,
            'company_id' => 1,
            'product_id' => 1,
            'name' => 'Lançamento Vitrine Social Mídia',
            'objective' => 'Gerar demonstrações comerciais qualificadas',
            'automation_mode' => 'assisted',
            'status' => 'ready',
            'known_facts' => [
                'Produto da Vitrine IA Pro',
                'Publicação depende de aprovação humana',
            ],
            'missing_information' => [],
            'restrictions' => [
                'Não publicar',
                'Não contratar mídia',
                'Não inventar preços',
            ],
        ];
    }
}
