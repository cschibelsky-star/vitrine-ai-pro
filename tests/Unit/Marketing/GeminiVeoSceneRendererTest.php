<?php

declare(strict_types=1);

namespace Tests\Unit\Marketing;

use App\Marketing\Domain\Video\VideoProject;
use App\Marketing\Domain\Video\VideoScene;
use App\Marketing\Infrastructure\Video\GeminiVeoSceneRenderer;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class GeminiVeoSceneRendererTest extends TestCase
{
    public function test_dispatches_vertical_veo_generation_without_spending_live_credits(): void
    {
        config()->set('marketing_video.gemini_veo.api_key', 'test-key');
        config()->set('marketing_video.gemini_veo.model', 'veo-3.1-generate-preview');
        config()->set('marketing_video.gemini_veo.base_url', 'https://generativelanguage.googleapis.com/v1beta');

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['name' => 'operations/test-operation'], 200),
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

        $this->assertSame('gemini_veo', $result['provider']);
        $this->assertSame('processing', $result['status']);
        $this->assertSame('operations/test-operation', $result['job_ref']);
        $this->assertNull($result['render_ref']);

        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), 'veo-3.1-generate-preview:predictLongRunning')
                && $request['parameters']['aspectRatio'] === '9:16'
                && $request['parameters']['durationSeconds'] === 8
                && $request['parameters']['sampleCount'] === 1;
        });
    }

    public function test_refresh_maps_completed_operation_to_render_reference(): void
    {
        config()->set('marketing_video.gemini_veo.api_key', 'test-key');
        config()->set('marketing_video.gemini_veo.base_url', 'https://generativelanguage.googleapis.com/v1beta');

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'done' => true,
                'response' => [
                    'generateVideoResponse' => [
                        'generatedSamples' => [[
                            'video' => ['uri' => 'https://example.test/video.mp4'],
                        ]],
                    ],
                ],
            ], 200),
        ]);

        $result = app(GeminiVeoSceneRenderer::class)->refresh('operations/test-operation');

        $this->assertSame('completed', $result['status']);
        $this->assertSame('https://example.test/video.mp4', $result['render_ref']);
    }
}
