<?php

declare(strict_types=1);

namespace Tests\Unit\Marketing;

use App\Marketing\Application\MarketingOrchestrator;
use App\Marketing\Application\ResilientMarketingAgentExecutor;
use App\Marketing\Application\SchemaContractValidator;
use Tests\TestCase;

final class MarketingGeminiLiveHomologationTest extends TestCase
{
    public function test_live_gemini_strategy_runs_operational_campaign_without_publish_or_spend(): void
    {
        if ((string) env('MARKETING_LIVE_HOMOLOGATION', '0') !== '1') {
            $this->markTestSkipped('Live marketing homologation is disabled.');
        }

        $executor = app(ResilientMarketingAgentExecutor::class);

        $result = app(MarketingOrchestrator::class)->runOperationalCampaign(
            $this->campaign(),
            $executor,
            app(SchemaContractValidator::class),
        );

        $metadata = $executor->metadataFor('product_market_strategist');

        $this->assertSame('gemini', $metadata['provider'] ?? null);
        $this->assertFalse($metadata['fallback'] ?? true);
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
