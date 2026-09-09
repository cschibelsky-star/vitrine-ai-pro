<?php

declare(strict_types=1);

namespace Tests\Unit\Marketing;

use App\Shared\AI\Video\GeminiVeoVideoProvider;
use App\Shared\AI\Video\VideoRequest;
use App\Shared\AI\Video\VideoSession;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

final class GeminiVeoVideoProviderTest extends TestCase
{
    public function test_it_generates_a_vertical_video_and_polls_the_long_running_operation(): void
    {
        config()->set('marketing_video.gemini_veo.api_key', 'test-key');
        config()->set('marketing_video.gemini_veo.base_url', 'https://generativelanguage.googleapis.com/v1beta');
        config()->set('marketing_video.gemini_veo.model', 'veo-3.1-generate-preview');
        config()->set('marketing_video.gemini_veo.poll_interval_seconds', 0);
        config()->set('marketing_video.gemini_veo.max_wait_seconds', 2);

        Http::fake([
            '*models/veo-3.1-generate-preview:predictLongRunning' => Http::response([
                'name' => 'operations/video-123',
            ], 200),
            '*operations/video-123' => Http::response([
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

        $request = new VideoRequest(
            requestId: 'REQ-1',
            source: 'marketing_agents',
            sourceId: 'video_producer',
            title: 'Vitrine Social Midia',
            script: 'Natural modern office, Brazilian entrepreneur using Vitrine Social Midia, cinematic camera movement, native Portuguese audio.',
            aspectRatios: ['9:16'],
            durationSeconds: 8,
        );
        $session = new VideoSession('SESSION-REQ-1', 'REQ-1');

        $version = (new GeminiVeoVideoProvider())->generate($request, $session);

        $this->assertSame('generated', $version->status);
        $this->assertSame('gemini_veo', $version->provider);
        $this->assertSame('operations/video-123', $version->providerJobId);
        $this->assertSame('https://example.test/video.mp4', $version->videoUrl);
        $this->assertSame('9:16', $version->aspectRatio);
        $this->assertTrue($version->metadata['audio_native']);

        Http::assertSent(function ($request): bool {
            if (! str_contains($request->url(), ':predictLongRunning')) {
                return true;
            }

            $payload = $request->data();

            return $request->hasHeader('x-goog-api-key', 'test-key')
                && data_get($payload, 'instances.0.prompt') !== null
                && data_get($payload, 'parameters.aspectRatio') === '9:16'
                && data_get($payload, 'parameters.resolution') === '720p'
                && data_get($payload, 'parameters.durationSeconds') === '8';
        });
    }

    public function test_it_rejects_1080p_when_duration_is_not_eight_seconds(): void
    {
        config()->set('marketing_video.gemini_veo.api_key', 'test-key');

        $request = new VideoRequest(
            requestId: 'REQ-2',
            source: 'marketing_agents',
            sourceId: 'video_producer',
            title: 'Invalid request',
            script: 'Prompt',
            aspectRatios: ['9:16'],
            durationSeconds: 6,
            metadata: ['resolution' => '1080p'],
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('requires an 8-second duration');

        (new GeminiVeoVideoProvider())->generate($request, new VideoSession('SESSION-REQ-2', 'REQ-2'));
    }

    public function test_it_refuses_generation_without_api_key(): void
    {
        config()->set('marketing_video.gemini_veo.api_key', null);

        $request = new VideoRequest(
            requestId: 'REQ-3',
            source: 'marketing_agents',
            sourceId: 'video_producer',
            title: 'No key',
            script: 'Prompt',
            aspectRatios: ['9:16'],
            durationSeconds: 8,
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('GEMINI_API_KEY');

        (new GeminiVeoVideoProvider())->generate($request, new VideoSession('SESSION-REQ-3', 'REQ-3'));
    }
}
