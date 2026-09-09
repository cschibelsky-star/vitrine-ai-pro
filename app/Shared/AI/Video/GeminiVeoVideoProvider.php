<?php

declare(strict_types=1);

namespace App\Shared\AI\Video;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class GeminiVeoVideoProvider implements VideoProvider
{
    public function providerId(): string
    {
        return 'gemini_veo';
    }

    public function supports(VideoRequest $request): bool
    {
        if ($request->script === '') {
            return false;
        }

        if (count($request->aspectRatios) !== 1 || ! in_array($request->aspectRatios[0], ['16:9', '9:16'], true)) {
            return false;
        }

        return $request->durationSeconds === null || in_array($request->durationSeconds, [4, 6, 8], true);
    }

    public function generate(VideoRequest $request, VideoSession $session): VideoVersion
    {
        if (! $this->supports($request)) {
            throw new RuntimeException('Unsupported Veo 3.1 video request. Use one aspect ratio (16:9 or 9:16) and duration 4, 6 or 8 seconds.');
        }

        $apiKey = (string) config('marketing_video.gemini_veo.api_key');
        $baseUrl = rtrim((string) config('marketing_video.gemini_veo.base_url'), '/');
        $model = (string) config('marketing_video.gemini_veo.model', 'veo-3.1-generate-preview');
        $resolution = (string) ($request->metadata['resolution'] ?? config('marketing_video.gemini_veo.resolution', '720p'));
        $duration = $request->durationSeconds ?? (int) config('marketing_video.gemini_veo.duration_seconds', 8);

        if ($apiKey === '') {
            throw new RuntimeException('GEMINI_API_KEY is not configured for the Video Producer.');
        }

        if (! in_array($resolution, ['720p', '1080p', '4k'], true)) {
            throw new RuntimeException('Unsupported Veo 3.1 resolution. Use 720p, 1080p or 4k.');
        }

        if (in_array($resolution, ['1080p', '4k'], true) && $duration !== 8) {
            throw new RuntimeException('Veo 3.1 requires an 8-second duration for 1080p and 4k generation.');
        }

        $parameters = [
            'aspectRatio' => $request->aspectRatios[0],
            'resolution' => $resolution,
            'durationSeconds' => (string) $duration,
        ];

        if (isset($request->metadata['seed'])) {
            $parameters['seed'] = (int) $request->metadata['seed'];
        }

        $response = $this->client()->post(
            sprintf('%s/models/%s:predictLongRunning', $baseUrl, rawurlencode($model)),
            [
                'instances' => [['prompt' => $request->script]],
                'parameters' => $parameters,
            ],
        );

        if (! $response->successful()) {
            throw new RuntimeException(sprintf('Veo 3.1 generation request failed with HTTP %d: %s', $response->status(), $response->body()));
        }

        $operationName = (string) $response->json('name', '');
        if ($operationName === '') {
            throw new RuntimeException('Veo 3.1 did not return a long-running operation name.');
        }

        $operation = $this->waitUntilDone($baseUrl, $operationName);
        $error = $operation['error'] ?? null;
        if (is_array($error)) {
            throw new RuntimeException('Veo 3.1 operation failed: '.(string) ($error['message'] ?? 'unknown provider error'));
        }

        $videoUrl = data_get($operation, 'response.generateVideoResponse.generatedSamples.0.video.uri');
        if (! is_string($videoUrl) || $videoUrl === '') {
            throw new RuntimeException('Veo 3.1 operation completed without a generated video URI.');
        }

        $number = (int) ($request->metadata['version_number'] ?? 1);
        $versionId = (string) ($request->metadata['version_id'] ?? sprintf('%s-V%d', $session->sessionId, $number));

        return new VideoVersion(
            versionId: $versionId,
            sessionId: $session->sessionId,
            number: $number,
            aspectRatio: $request->aspectRatios[0],
            status: 'generated',
            provider: $this->providerId(),
            providerJobId: $operationName,
            videoUrl: $videoUrl,
            metadata: [
                'model' => $model,
                'resolution' => $resolution,
                'duration_seconds' => $duration,
                'audio_native' => true,
                'download_requires_api_key' => true,
                'provider_retention_days' => 2,
            ],
        );
    }

    /** @return array<string, mixed> */
    private function waitUntilDone(string $baseUrl, string $operationName): array
    {
        $pollInterval = max(0, (int) config('marketing_video.gemini_veo.poll_interval_seconds', 10));
        $maxWait = max(1, (int) config('marketing_video.gemini_veo.max_wait_seconds', 360));
        $maxAttempts = max(1, (int) ceil($maxWait / max(1, $pollInterval)));

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $response = $this->client()->get($baseUrl.'/'.ltrim($operationName, '/'));

            if (! $response->successful()) {
                throw new RuntimeException(sprintf('Veo 3.1 operation polling failed with HTTP %d: %s', $response->status(), $response->body()));
            }

            /** @var array<string, mixed> $operation */
            $operation = $response->json();
            if (($operation['done'] ?? false) === true) {
                return $operation;
            }

            if ($attempt < $maxAttempts && $pollInterval > 0) {
                sleep($pollInterval);
            }
        }

        throw new RuntimeException(sprintf('Veo 3.1 generation timed out after %d seconds.', $maxWait));
    }

    private function client(): PendingRequest
    {
        return Http::acceptJson()
            ->asJson()
            ->withHeaders(['x-goog-api-key' => (string) config('marketing_video.gemini_veo.api_key')])
            ->connectTimeout((int) config('marketing_video.gemini_veo.connect_timeout_seconds', 10))
            ->timeout((int) config('marketing_video.gemini_veo.timeout_seconds', 30))
            ->retry(
                (int) config('marketing_video.gemini_veo.http_retries', 2),
                (int) config('marketing_video.gemini_veo.http_retry_delay_ms', 500),
                throw: false,
            );
    }
}
