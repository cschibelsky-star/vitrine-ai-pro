<?php

namespace Tests\Unit\Marketing;

use App\Marketing\Application\DependencyEngine;
use App\Marketing\Domain\Campaigns\CampaignState;
use App\Marketing\Domain\Campaigns\CampaignStatus;
use Tests\TestCase;

class DependencyEngineTest extends TestCase
{
    public function test_refresh_marks_root_tasks_ready_and_campaign_ready(): void
    {
        $campaign = new CampaignState('cmp-1', 'product-1', 'Sell product', 'SMBs', ['instagram']);
        $campaign->registerTask('strategy');
        $campaign->registerTask('planner', ['strategy']);

        $ready = app(DependencyEngine::class)->refresh($campaign);

        $this->assertSame(['strategy'], $ready);
        $this->assertSame(CampaignStatus::Ready, $campaign->status());
        $this->assertTrue(app(DependencyEngine::class)->canStart($campaign, 'strategy'));
        $this->assertFalse(app(DependencyEngine::class)->canStart($campaign, 'planner'));
    }

    public function test_design_and_video_become_executable_in_parallel_and_social_waits_for_both(): void
    {
        $campaign = new CampaignState('cmp-2', 'product-1', 'Sell product', 'SMBs', ['instagram']);
        $campaign->registerTask('copy');
        $campaign->registerTask('creative', ['copy']);
        $campaign->registerTask('video', ['copy']);
        $campaign->registerTask('social', ['creative', 'video']);

        $engine = app(DependencyEngine::class);
        $engine->refresh($campaign);
        $campaign->startTask('copy', '2026-09-04T12:00:00Z');
        $campaign->completeTask('copy', '2026-09-04T12:01:00Z');

        $this->assertSame(['creative', 'video'], $engine->executableTaskIds($campaign));

        $campaign->startTask('creative', '2026-09-04T12:02:00Z');
        $campaign->completeTask('creative', '2026-09-04T12:03:00Z');
        $this->assertFalse($engine->canStart($campaign, 'social'));

        $campaign->startTask('video', '2026-09-04T12:02:00Z');
        $campaign->completeTask('video', '2026-09-04T12:04:00Z');

        $this->assertSame(['social'], $engine->executableTaskIds($campaign));
    }

    public function test_engine_does_not_release_tasks_when_campaign_is_blocked(): void
    {
        $campaign = new CampaignState('cmp-3', 'product-1', 'Sell product', 'SMBs', ['instagram']);
        $campaign->registerTask('strategy');
        $campaign->block('qa blocked');

        $engine = app(DependencyEngine::class);

        $this->assertSame([], $engine->refresh($campaign));
        $this->assertSame([], $engine->executableTaskIds($campaign));
        $this->assertFalse($engine->canStart($campaign, 'strategy'));
    }
}
