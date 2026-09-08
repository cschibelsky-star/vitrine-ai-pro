<?php

namespace Tests\Unit\Marketing;

use App\Marketing\Application\SimulatedMarketingAgentExecutor;
use App\Shared\AI\Video\VideoEngine;
use App\Shared\AI\Video\VideoRequest;
use Tests\TestCase;

class VideoEngineFoundationTest extends TestCase
{
    public function test_shared_engine_plans_versions_without_provider_execution(): void
    {
        $request = new VideoRequest(
            requestId: 'REQ-001',
            source: 'marketing_agents',
            sourceId: 'CMP-001',
            title: 'Campanha de teste',
            script: 'Roteiro de teste.',
            aspectRatios: ['9:16', '1:1'],
            durationSeconds: 30,
        );

        $session = (new VideoEngine())->plan($request);

        $this->assertSame('SESSION-REQ-001', $session->sessionId);
        $this->assertSame('planned', $session->status);
        $this->assertSame('plan_only', $session->metadata['execution_mode']);
        $this->assertCount(2, $session->versions);
        $this->assertSame('9:16', $session->versions[0]->aspectRatio);
        $this->assertNull($session->versions[0]->provider);
        $this->assertSame('1:1', $session->versions[1]->aspectRatio);
    }

    public function test_marketing_video_producer_exposes_shared_engine_plan_without_publishing_or_spending(): void
    {
        $executor = new SimulatedMarketingAgentExecutor();
        $campaign = [
            'campaign_id' => 'CMP-001',
            'objective' => 'Gerar demonstrações',
        ];
        $inputs = [
            'copy_content' => [
                'message_hierarchy' => [
                    'headline' => 'Sua marca sempre presente.',
                    'support' => 'Planejamento e conteúdo com IA.',
                    'cta' => 'Solicitar demonstração',
                ],
                'video_scripts' => [
                    ['id' => 'VIDEO-001', 'duration_seconds' => 30],
                ],
            ],
        ];

        $output = $executor->execute('video_producer', $campaign, $inputs);
        $engine = $output['render_requirements']['video_engine'];

        $this->assertSame('MARKETING-VIDEO-CMP-001', $engine['request_id']);
        $this->assertSame('SESSION-MARKETING-VIDEO-CMP-001', $engine['session_id']);
        $this->assertSame('plan_only', $engine['mode']);
        $this->assertCount(2, $engine['versions']);
        $this->assertSame('storyboard_ready', $output['videos'][0]['status']);
        $this->assertArrayNotHasKey('published', $output);
        $this->assertArrayNotHasKey('spent', $output);
    }
}
