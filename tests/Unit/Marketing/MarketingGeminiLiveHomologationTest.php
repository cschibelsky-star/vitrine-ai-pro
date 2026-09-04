<?php

declare(strict_types=1);

namespace Tests\Unit\Marketing;

use App\Marketing\Application\GeminiStrategyAgent;
use App\Marketing\Application\MarketingAgentExecutor;
use App\Marketing\Application\MarketingOrchestrator;
use App\Marketing\Application\SchemaContractValidator;
use App\Marketing\Application\SimulatedMarketingAgentExecutor;
use Tests\TestCase;
use Throwable;

final class MarketingGeminiLiveHomologationTest extends TestCase
{
    public function test_live_gemini_strategy_runs_operational_campaign_without_publish_or_spend(): void
    {
        if ((string) getenv('MARKETING_LIVE_HOMOLOGATION') !== '1') {
            $this->markTestSkipped('Live marketing homologation is disabled.');
        }

        $campaign = $this->campaign();
        $strategyAgent = app(GeminiStrategyAgent::class);

        try {
            $strategy = $strategyAgent->execute($campaign);
        } catch (Throwable $exception) {
            $previous = $exception->getPrevious();
            $this->fail(sprintf(
                'Gemini live request failed: %s: %s; previous=%s: %s',
                $exception::class,
                $exception->getMessage(),
                $previous ? $previous::class : 'none',
                $previous?->getMessage() ?? 'none',
            ));
        }

        $metadata = $strategyAgent->metadata();
        $this->assertSame('gemini', $metadata['provider'] ?? null);
        $this->assertFalse($metadata['fallback'] ?? true);

        $simulated = app(SimulatedMarketingAgentExecutor::class);
        $executor = new class($strategy, $metadata, $simulated) implements MarketingAgentExecutor {
            public function __construct(
                private readonly array $strategy,
                private readonly array $strategyMetadata,
                private readonly SimulatedMarketingAgentExecutor $simulated,
            ) {
            }

            public function execute(string $agentId, array $campaign, array $inputs): array
            {
                if ($agentId === 'product_market_strategist') {
                    return $this->strategy;
                }

                return $this->simulated->execute($agentId, $campaign, $inputs);
            }

            public function metadataFor(string $agentId): array
            {
                if ($agentId === 'product_market_strategist') {
                    return $this->strategyMetadata;
                }

                return $this->simulated->metadataFor($agentId);
            }
        };

        $result = app(MarketingOrchestrator::class)->runOperationalCampaign(
            $campaign,
            $executor,
            app(SchemaContractValidator::class),
        );

        $this->assertSame('completed', $result['status']);
        $this->assertSame('approved', $result['qa_result']);
        $this->assertFalse($result['published']);
        $this->assertFalse($result['spent']);
        $this->assertSame([
            ['product_market_strategist'],
            ['campaign_planner'],
            ['copy_content'],
            ['creative_director', 'video_producer'],
            ['social_distribution'],
            ['qa_brand_guardian'],
        ], $result['execution_batches']);
    }

    /** @return array<string, mixed> */
    private function campaign(): array
    {
        return [
            'campaign_id' => 'VSM-GEMINI-HML-001',
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
