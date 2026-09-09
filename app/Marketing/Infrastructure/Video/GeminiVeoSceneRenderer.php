<?php

declare(strict_types=1);

namespace App\Marketing\Infrastructure\Video;

use App\Marketing\Application\VideoSceneRenderer;
use App\Marketing\Domain\Video\VideoProject;
use App\Marketing\Domain\Video\VideoScene;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

final class GeminiVeoSceneRenderer implements VideoSceneRenderer
{
    public function dispatch(VideoProject $project, VideoScene $scene, array $context = []): array
    {
        $prompt = trim((string) ($scene->content()['prompt'] ?? $scene->content()['script'] ?? $scene->content()['text'] ?? ''));
        if ($prompt === '') {
            throw new InvalidArgumentException('video_scene_prompt_required:'.$scene->sceneId);
        }

        $model = trim((string) config('marketing_video.gemini_veo.model', 'veo-3.1-generate-preview'));
        $aspectRatio = (string) ($context['aspect_ratio'] ?? config('marketing_video.gemini_veo.aspect_ratio', '9:16'));
        $resolution = (string) ($context['resolution'] ?? config('marketing_video.gemini_veo.resolution', '720p'));
        $duration = (int) ($context['duration_seconds'] ?? 8);

        if (! in_array($aspectRatio, ['9:16', '16:9'], true)) {
            throw new InvalidArgumentException('veo_aspect_ratio_invalid');
        }
        if (! in_array($duration, [4, 6, 8], true)) {
            throw new InvalidArgumentException('veo_duration_invalid');
        }

        $payload = [
            'instances' => [[
                'prompt' => $prompt,
            ]],
            'parameters' => [
                'aspectRatio' => $aspectRatio,
                'resolution' => $resolution,
                'durationSeconds' => $duration,
                'sampleCount' => 1,
            ],
        ];

        $response = $this->client()->post(sprintf('/models/%s:predictLongRunning', $model), $payload);
        if (! $response->successful()) {
            throw new RuntimeException('gemini_veo_dispatch_failed:'.$response->status());
        }

        $operation = trim((string) $response->json('name', ''));
        if ($operation === '') {
            throw new RuntimeException('gemini_veo_operation_missing');
        }

        return [
            'provider' => 'gemini_veo',
            'status' => 'processing',
            'job_ref' => $operation,
            'render_ref' => null,
        ];
    }

    public function refresh(string $jobRef): array
    {
        $jobRef = trim($jobRef);
        if ($jobRef === '' || ! str_starts_with($jobRef, 'operations/')) {
            throw new InvalidArgumentException('gemini_veo_job_ref_invalid');
        }

        $response = $this->client()->get('/'.$jobRef);
        if (! $response->successful()) {
            throw new RuntimeException('gemini_veo_refresh_failed:'.$response->status());
        }

        if (! (bool) $response->json('done', false)) {
            return [
                'provider' => 'gemini_veo',
                'status' => 'processing',
                'job_ref' => $jobRef,
                'render_ref' => null,
            ];
        }

        if ($response->json('error') !== null) {
            return [
                'provider' => 'gemini_veo',
                'status' => 'failed',
                'job_ref' => $jobRef,
                'render_ref' => null,
            ];
        }

        $renderRef = $response->json('response.generateVideoResponse.generatedSamples.0.video.uri')
            ?? $response->json('response.generatedVideos.0.video.uri');

        if (! is_string($renderRef) || trim($renderRef) === '') {
            throw new RuntimeException('gemini_veo_render_ref_missing');
        }

        return [
            'provider' => 'gemini_veo',
            'status' => 'completed',
            'job_ref' => $jobRef,
            'render_ref' => $renderRef,
        ];
    }

    private function client(): PendingRequest
    {
        $apiKey = trim((string) config('marketing_video.gemini_veo.api_key'));
        if ($apiKey === '') {
            throw new RuntimeException('gemini_api_key_missing');
        }

        return Http::baseUrl(rtrim((string) config('marketing_video.gemini_veo.base_url'), '/'))
            ->acceptJson()
            ->withHeaders(['x-goog-api-key' => $apiKey])
            ->timeout((int) config('marketing_video.gemini_veo.timeout_seconds', 30));
    }
}
