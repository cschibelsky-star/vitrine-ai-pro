<?php

declare(strict_types=1);

namespace Tests\Unit\Marketing;

use App\Marketing\Application\VideoSceneRenderer;
use App\Marketing\Domain\Video\VideoProject;
use App\Marketing\Domain\Video\VideoScene;
use App\Marketing\Infrastructure\Video\GeminiVeoSceneRenderer;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class GeminiVeoSceneRendererTest extends TestCase
{
    public function test_default_marketing_video_renderer_remains_veo_even_if_legacy_provider_env_requests_heygen(): void
    {
        config()->set('marketing_video.provider', 'heygen');

        $renderer = app(VideoSceneRenderer::class);

        $this->assertInstanceOf(GeminiVeoSceneRenderer::class, $renderer);
        $this->assertSame('gemini_veo', config('marketing_video.routing.video_default'));
        $this->assertSame('presenter_avatar_voice_only', config('marketing_video.routing.heygen_usage'));
    }

    public function test_dispatches_video_only_through_centro_ia_without_direct_provider_call(): void
    {
        config()->set('marketing_agents.hub.url', 'https://centro-ia.test/execute');
        config()->set('marketing_agents.hub.token', 'test-token');
        config()->set('marketing_agents.hub.project_id', 'marketing-test');

        Http::fake([
            'https://centro-ia.test/execute' => Http::response([
                'ok' => true,
                'media_status' => 'processing',
                'job_ref' => 'provider-job-123',
                'model' => 'hub-routed-video',
            ], 200),
        ]);

        $project = new VideoProject(
            projectId: 'reel-001',
            productId: 'vitrine-social-midia',
            campaignId: 'campaign-test-001',
        );
        $scene = new VideoScene('scene-01', 1, ['prompt' => 'Photorealistic modern office scene, no text overlays.']);

        $result = app(GeminiVeoSceneRenderer::class)->dispatch($project, $scene, [
            'aspect_ratio' => '9:16',
            'duration_seconds' => 8,
        ]);

        $this->assertSame('centro_ia', $result['provider']);
        $this->assertSame('processing', $result['status']);
        $this->assertStringStartsWith('centroia:', $result['job_ref']);
        $this->assertNull($result['render_ref']);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://centro-ia.test/execute'
                && $request['capability'] === 'video_generation'
                && $request['input']['aspect_ratio'] === '9:16'
                && $request['input']['duration_seconds'] === 8;
        });
    }

    public function test_refreshes_centro_ia_job_without_direct_provider_call(): void
    {
        config()->set('marketing_agents.hub.url', 'https://centro-ia.test/execute');
        config()->set('marketing_agents.hub.token', 'test-token');
        config()->set('marketing_agents.hub.project_id', 'marketing-test');

        $encoded = base64_encode(json_encode([
            'job_ref' => 'provider-job-123',
            'model' => 'hub-routed-video',
        ], JSON_UNESCAPED_SLASHES));

        Http::fake([
            'https://centro-ia.test/execute' => Http::response([
                'ok' => true,
                'media_status' => 'completed',
                'asset_url' => 'https://example.test/video.mp4',
            ], 200),
        ]);

        $result = app(GeminiVeoSceneRenderer::class)->refresh('centroia:'.$encoded);

        $this->assertSame('completed', $result['status']);
        $this->assertSame('https://example.test/video.mp4', $result['render_ref']);
    }
}
