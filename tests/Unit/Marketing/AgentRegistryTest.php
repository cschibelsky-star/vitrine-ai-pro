<?php

namespace Tests\Unit\Marketing;

use App\Marketing\Domain\Agents\AgentRegistry;
use Tests\TestCase;

class AgentRegistryTest extends TestCase
{
    public function test_registry_contains_the_nine_v1_agents_and_is_valid(): void
    {
        $registry = app(AgentRegistry::class);

        $registry->assertValid();

        $this->assertCount(9, $registry->all());
        $this->assertFalse($registry->isEnabled('performance_analyst'));
        $this->assertTrue($registry->get('qa_brand_guardian')['may_block_pipeline']);
    }

    public function test_design_and_video_run_after_copy_and_social_waits_for_both(): void
    {
        $registry = app(AgentRegistry::class);

        $this->assertSame(['copy_content'], $registry->dependenciesOf('creative_director'));
        $this->assertSame(['copy_content'], $registry->dependenciesOf('video_producer'));
        $this->assertSame(['creative_director', 'video_producer'], $registry->dependenciesOf('social_distribution'));
    }

    public function test_no_v1_agent_can_publish_or_spend(): void
    {
        foreach (app(AgentRegistry::class)->all() as $agent) {
            $this->assertFalse($agent['may_publish']);
            $this->assertFalse($agent['may_spend']);
        }
    }
}
