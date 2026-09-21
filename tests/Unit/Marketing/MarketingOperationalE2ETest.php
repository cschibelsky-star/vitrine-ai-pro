<?php

namespace Tests\Unit\Marketing;

use App\Filament\Pages\MarketingDashboard;
use App\Marketing\Application\MarketingAgentExecutor;
use App\Marketing\Application\MarketingOrchestrator;
use App\Marketing\Application\SchemaContractValidator;
use App\Marketing\Application\SimulatedMarketingAgentExecutor;
use Illuminate\Support\Facades\Http;
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

    public function test_qa_block_blocks_campaign_and_still_never_publishes_or_spends(): void
    {
        $executor = new class(app(SimulatedMarketingAgentExecutor::class)) implements MarketingAgentExecutor {
            public function __construct(private SimulatedMarketingAgentExecutor $delegate)
            {
            }

            public function execute(string $agentId, array $campaign, array $inputs): array
            {
                $output = $this->delegate->execute($agentId, $campaign, $inputs);

                if ($agentId === 'qa_brand_guardian') {
                    $output['result'] = 'blocked';
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
        $this->assertSame('blocked', $result['qa_result']);
        $this->assertFalse($result['published']);
        $this->assertFalse($result['spent']);
        $this->assertSame('blocked', $result['state']['tasks']['qa_brand_guardian']['status']);
        $this->assertSame('QA did not approve the campaign.', $result['state']['blocked_reason']);
    }

    public function test_marketing_dashboard_recognizes_revision_language_and_targets_existing_piece(): void
    {
        $page = app(MarketingDashboard::class);
        $page->flowJobs = [
            [
                'id' => 'MKT-AUTO-REV-001',
                'title' => 'Card Lista VIP',
                'status' => 'EM_QA',
                'type' => 'image',
                'format' => 'ad_1_1',
            ],
        ];

        $revisionDetector = new \ReflectionMethod($page, 'isRevisionRequest');
        $revisionDetector->setAccessible(true);
        $this->assertTrue($revisionDetector->invoke($page, 'Corrija o Card Lista VIP e aumente o destaque do CTA.'));
        $this->assertFalse($revisionDetector->invoke($page, 'Crie uma nova campanha para amanhã.'));

        $targetResolver = new \ReflectionMethod($page, 'resolveRevisionTargetIndex');
        $targetResolver->setAccessible(true);
        $this->assertSame(0, $targetResolver->invoke($page, 'Ajustar o Card Lista VIP.'));
    }

    public function test_marketing_dashboard_routes_structured_campaign_plan_through_centro_ia_first(): void
    {
        config()->set('marketing_agents.hub.url', 'https://centro-ia.test/execute');
        config()->set('marketing_agents.hub.token', 'test-token');
        config()->set('marketing_agents.hub.project_id', 'marketing-test');
        config()->set('marketing_agents.hub.capability', 'marketing_generation');

        Http::fake([
            'https://centro-ia.test/execute' => Http::response([
                'ok' => true,
                'output_text' => '{"campaign":{"name":"Hub"},"jobs":[{"type":"image","format":"ad_1_1"}]}',
                'model' => 'hub-test',
            ], 200),
        ]);

        $page = app(MarketingDashboard::class);
        $method = new \ReflectionMethod($page, 'generateDirectorCampaignPlan');
        $method->setAccessible(true);
        $raw = $method->invoke($page, 'system', 'user prompt');

        $this->assertStringContainsString('"name":"Hub"', $raw);
        Http::assertSentCount(1);
    }

    public function test_marketing_dashboard_accepts_director_json_wrapped_in_markdown(): void
    {
        $page = app(MarketingDashboard::class);
        $decoder = new \ReflectionMethod($page, 'decodeDirectorPlan');
        $decoder->setAccessible(true);

        $plan = $decoder->invoke($page, "Resposta do Diretor:\n\x60\x60\x60json\n{\"campaign\":{\"name\":\"Teste\"},\"jobs\":[{\"type\":\"image\",\"format\":\"ad_1_1\"}]}\n\x60\x60\x60");

        $this->assertSame('Teste', $plan['campaign']['name']);
        $this->assertCount(1, $plan['jobs']);
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
