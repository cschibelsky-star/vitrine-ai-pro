<?php

namespace Tests\Unit\Marketing;

use App\Marketing\Domain\Approvals\ApprovalDecision;
use App\Marketing\Domain\Campaigns\CampaignState;
use App\Marketing\Domain\Campaigns\CampaignStatus;
use App\Marketing\Domain\Tasks\TaskStatus;
use PHPUnit\Framework\TestCase;

class CampaignStateTest extends TestCase
{
    public function test_campaign_tracks_dependencies_and_parallel_readiness(): void
    {
        $state = $this->state();
        $state->registerTask('product_market_strategist');
        $state->registerTask('campaign_planner', ['product_market_strategist']);
        $state->registerTask('copy_content', ['product_market_strategist', 'campaign_planner']);
        $state->registerTask('creative_director', ['copy_content']);
        $state->registerTask('video_producer', ['copy_content']);
        $state->registerTask('social_distribution', ['creative_director', 'video_producer']);

        $this->assertSame(['product_market_strategist'], $state->readyTaskIds());

        $state->startTask('product_market_strategist', '2026-09-04T12:00:00Z');
        $state->completeTask('product_market_strategist', '2026-09-04T12:01:00Z', 'artifact://strategy');
        $this->assertSame(['campaign_planner'], $state->readyTaskIds());

        $state->completeTask('campaign_planner', '2026-09-04T12:02:00Z');
        $state->completeTask('copy_content', '2026-09-04T12:03:00Z');
        $this->assertSame(['creative_director', 'video_producer'], $state->readyTaskIds());

        $state->completeTask('creative_director', '2026-09-04T12:04:00Z');
        $this->assertFalse($state->allDependenciesCompleted('social_distribution'));
        $state->completeTask('video_producer', '2026-09-04T12:05:00Z');
        $this->assertTrue($state->allDependenciesCompleted('social_distribution'));
    }

    public function test_campaign_blocks_on_failed_task_and_serializes_state(): void
    {
        $state = $this->state();
        $state->registerTask('qa_brand_guardian');
        $state->startTask('qa_brand_guardian', '2026-09-04T12:00:00Z');
        $state->failTask('qa_brand_guardian', 'brand policy violation');
        $state->setApproval('qa', ApprovalDecision::RevisionRequested, 'Adjust CTA');
        $state->addArtifact(['type' => 'copy', 'ref' => 'artifact://copy-1']);

        $data = $state->toArray();

        $this->assertSame(CampaignStatus::Blocked, $state->status());
        $this->assertSame('blocked', $data['status']);
        $this->assertSame('failed', $data['tasks']['qa_brand_guardian']['status']);
        $this->assertSame('revision_requested', $data['approvals']['qa']['decision']);
        $this->assertSame('brand policy violation', $data['tasks']['qa_brand_guardian']['last_error']);
    }

    public function test_task_lifecycle_tracks_attempts_and_completion(): void
    {
        $state = $this->state();
        $state->registerTask('copy_content');
        $state->markTaskReady('copy_content');
        $this->assertSame(TaskStatus::Ready, $state->task('copy_content')['status']);

        $state->startTask('copy_content', '2026-09-04T12:00:00Z');
        $state->completeTask('copy_content', '2026-09-04T12:00:05Z', 'artifact://copy');

        $task = $state->task('copy_content');
        $this->assertSame(TaskStatus::Completed, $task['status']);
        $this->assertSame(1, $task['attempts']);
        $this->assertSame('artifact://copy', $task['output_ref']);
    }

    private function state(): CampaignState
    {
        return new CampaignState(
            campaignId: 'cmp-001',
            productId: 'vitrine-social-midia',
            objective: 'Gerar demanda para o produto',
            audience: 'Pequenos negocios',
            channels: ['instagram', 'facebook'],
        );
    }
}
