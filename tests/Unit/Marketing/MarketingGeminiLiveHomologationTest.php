<?php

declare(strict_types=1);

namespace Tests\Unit\Marketing;

use App\Marketing\Application\MarketingOrchestrator;
use App\Marketing\Application\ResilientMarketingAgentExecutor;
use App\Marketing\Application\SchemaContractValidator;
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
        $executor = app(ResilientMarketingAgentExecutor::class);

        try {
            $result = app(MarketingOrchestrator::class)->runOperationalCampaign(
                $campaign,
                $executor,
                app(SchemaContractValidator::class),
            );
        } catch (Throwable $exception) {
            $previous = $exception->getPrevious();
            $this->fail(sprintf(
                'Marketing live pipeline failed: %s: %s; previous=%s: %s',
                $exception::class,
                $exception->getMessage(),
                $previous ? $previous::class : 'none',
                $previous?->getMessage() ?? 'none',
            ));
        }

        foreach (['product_market_strategist', 'campaign_planner', 'copy_content', 'creative_director', 'social_distribution', 'qa_brand_guardian'] as $agentId) {
            $metadata = $result['execution_metadata'][$agentId] ?? [];
            $this->assertSame('centro-ia', $metadata['provider'] ?? null, "{$agentId} must execute via Centro IA. Metadata: ".json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $this->assertFalse($metadata['fallback'] ?? true, "{$agentId} must not use fallback.");
        }

        $videoMetadata = $result['execution_metadata']['video_producer'] ?? [];
        $this->assertSame('video-engine-plan', $videoMetadata['provider'] ?? null);
        $this->assertSame('explicit', $videoMetadata['generation_mode'] ?? null);

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
