<?php

namespace Tests\Unit\Marketing;

use App\Marketing\Application\MarketingOrchestrator;
use App\Marketing\Domain\Tasks\TaskStatus;
use LogicException;
use Tests\TestCase;

class MarketingOrchestratorTest extends TestCase
{
    public function test_campaign_starts_with_strategy_only(): void
    {
        $state = app(MarketingOrchestrator::class)->createCampaign('CAM-SOCIAL-001');

        $this->assertSame(['product_market_strategist'], app(MarketingOrchestrator::class)->readyAgents($state));
        $this->assertArrayNotHasKey('marketing_director', $state->tasks());
        $this->assertArrayNotHasKey('performance_analyst', $state->tasks());
    }

    public function test_copy_releases_design_and_video_in_parallel(): void
    {
        $orchestrator = app(MarketingOrchestrator::class);
        $state = $orchestrator->createCampaign('CAM-SOCIAL-001');

        $this->finish($orchestrator, $state, 'product_market_strategist');
        $this->assertSame(['campaign_planner'], $orchestrator->readyAgents($state));

        $this->finish($orchestrator, $state, 'campaign_planner');
        $this->assertSame(['copy_content'], $orchestrator->readyAgents($state));

        $this->finish($orchestrator, $state, 'copy_content');

        $this->assertEqualsCanonicalizing(
            ['creative_director', 'video_producer'],
            $orchestrator->readyAgents($state),
        );
    }

    public function test_social_waits_until_design_and_video_are_complete(): void
    {
        $orchestrator = app(MarketingOrchestrator::class);
        $state = $this->advanceThroughCopy($orchestrator);

        $this->finish($orchestrator, $state, 'creative_director');
        $this->assertNotContains('social_distribution', $orchestrator->readyAgents($state));

        $this->finish($orchestrator, $state, 'video_producer');
        $this->assertSame(['social_distribution'], $orchestrator->readyAgents($state));
    }

    public function test_qa_revision_reopens_only_the_affected_branch(): void
    {
        $orchestrator = app(MarketingOrchestrator::class);
        $state = $this->advanceThroughQa($orchestrator);

        $orchestrator->requestRevision(
            $state,
            'qa_brand_guardian',
            'video_producer',
        );

        $this->assertSame(TaskStatus::Completed, $state->statusOf('creative_director'));
        $this->assertSame(TaskStatus::Ready, $state->statusOf('video_producer'));
        $this->assertSame(TaskStatus::Pending, $state->statusOf('social_distribution'));
        $this->assertSame(TaskStatus::Pending, $state->statusOf('qa_brand_guardian'));
    }

    public function test_non_validator_cannot_block_the_pipeline(): void
    {
        $orchestrator = app(MarketingOrchestrator::class);
        $state = $orchestrator->createCampaign('CAM-SOCIAL-001');

        $this->expectException(LogicException::class);
        $orchestrator->block($state, 'copy_content');
    }

    private function advanceThroughCopy(MarketingOrchestrator $orchestrator): object
    {
        $state = $orchestrator->createCampaign('CAM-SOCIAL-001');

        $this->finish($orchestrator, $state, 'product_market_strategist');
        $this->finish($orchestrator, $state, 'campaign_planner');
        $this->finish($orchestrator, $state, 'copy_content');

        return $state;
    }

    private function advanceThroughQa(MarketingOrchestrator $orchestrator): object
    {
        $state = $this->advanceThroughCopy($orchestrator);

        $this->finish($orchestrator, $state, 'creative_director');
        $this->finish($orchestrator, $state, 'video_producer');
        $this->finish($orchestrator, $state, 'social_distribution');
        $orchestrator->start($state, 'qa_brand_guardian');

        return $state;
    }

    private function finish(MarketingOrchestrator $orchestrator, object $state, string $agentId): void
    {
        $orchestrator->start($state, $agentId);
        $orchestrator->complete($state, $agentId);
    }
}
