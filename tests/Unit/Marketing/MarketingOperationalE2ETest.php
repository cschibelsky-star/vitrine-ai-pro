<?php

namespace Tests\Unit\Marketing;

use App\Marketing\Application\MarketingAgentExecutor;
use App\Marketing\Application\MarketingOrchestrator;
use App\Marketing\Application\SchemaContractValidator;
use App\Marketing\Application\SimulatedMarketingAgentExecutor;
use Tests\TestCase;

class MarketingOperationalE2ETest extends TestCase
{
    public function test_vitrine_social_midia_operational_e2e_sequence_is_complete_and_safe(): void
    {
        $result = app(MarketingOrchestrator::class)->runOperationalCampaign(
            $this->campaign(),
            app(SimulatedMarketingAgentExecutor::class),
            app(SchemaContractValidator::class),
        );

        $this->assertSame([
            ['product_market_strategist'],
            ['campaign_planner'],
            ['copy_content'],
            ['creative_director', 'video_producer'],
            ['social_distribution'],
            ['qa_brand_guardian'],
        ], $result['execution_batches']);

        $this->assertSame('completed', $result['status']);
        $this->assertSame('approved', $result['qa_result']);
        $this->assertFalse($result['published']);
        $this->assertFalse($result['spent']);
        $this->assertCount(7, $result['artifacts']);

        $expectedAgents = [
            'product_market_strategist',
            'campaign_planner',
            'copy_content',
            'creative_director',
            'video_producer',
            'social_distribution',
            'qa_brand_guardian',
        ];

        $this->assertSame($expectedAgents, array_keys($result['state']['tasks']));

        foreach ($result['state']['tasks'] as $task) {
            $this->assertSame('completed', $task['status']);
            $this->assertNotNull($task['output_ref']);
        }
    }

    public function test_qa_rejection_blocks_campaign_and_still_never_publishes_or_spends(): void
    {
        $executor = new class(app(SimulatedMarketingAgentExecutor::class)) implements MarketingAgentExecutor {
            public function __construct(private SimulatedMarketingAgentExecutor $delegate)
            {
            }

            public function execute(string $agentId, array $campaign, array $inputs): array
            {
                $output = $this->delegate->execute($agentId, $campaign, $inputs);

                if ($agentId === 'qa_brand_guardian') {
                    $output['result'] = 'rejected';
                    $output['summary']['blocking_issues'] = 1;
                    $output['issues'] = [['type' => 'brand', 'severity' => 'blocking']];
                    $output['approved_item_ids'] = [];
                    $output['revision_item_ids'] = array_keys($inputs);
                }

                return $output;
            }

            public function metadataFor(string $agentId): array
            {
                return ['provider' => 'simulated', 'fallback' => false];
            }
        };

        $result = app(MarketingOrchestrator::class)->runOperationalCampaign(
            $this->campaign(),
            $executor,
            app(SchemaContractValidator::class),
        );

        $this->assertSame('blocked', $result['status']);
        $this->assertSame('rejected', $result['qa_result']);
        $this->assertFalse($result['published']);
        $this->assertFalse($result['spent']);
        $this->assertSame('blocked', $result['state']['tasks']['qa_brand_guardian']['status']);
        $this->assertSame('QA did not approve the campaign.', $result['state']['blocked_reason']);
    }

    /** @return array<string, mixed> */
    private function campaign(): array
    {
        return [
            'campaign_id' => 'VSM-E2E-OP-001',
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
