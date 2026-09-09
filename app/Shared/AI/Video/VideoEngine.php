<?php

declare(strict_types=1);

namespace App\Shared\AI\Video;

use RuntimeException;

final class VideoEngine
{
    /** @param iterable<VideoProvider> $providers */
    public function __construct(private readonly iterable $providers = [])
    {
    }

    public function plan(VideoRequest $request): VideoSession
    {
        $session = new VideoSession(
            sessionId: 'SESSION-'.$request->requestId,
            requestId: $request->requestId,
            status: 'planned',
            metadata: [
                'source' => $request->source,
                'source_id' => $request->sourceId,
                'execution_mode' => 'plan_only',
            ],
        );

        foreach ($request->aspectRatios as $index => $aspectRatio) {
            $session->addVersion(new VideoVersion(
                versionId: sprintf('%s-V%d', $session->sessionId, $index + 1),
                sessionId: $session->sessionId,
                number: $index + 1,
                aspectRatio: $aspectRatio,
                status: 'planned',
                metadata: [
                    'duration_seconds' => $request->durationSeconds,
                ],
            ));
        }

        return $session;
    }

    public function generate(VideoRequest $request, ?string $providerId = null): VideoSession
    {
        $session = new VideoSession(
            sessionId: 'SESSION-'.$request->requestId,
            requestId: $request->requestId,
            status: 'generating',
            metadata: [
                'source' => $request->source,
                'source_id' => $request->sourceId,
                'execution_mode' => 'generate',
            ],
        );

        $provider = $this->resolveProvider($request, $providerId);

        foreach ($request->aspectRatios as $index => $aspectRatio) {
            $versionNumber = $index + 1;
            $singleVersionRequest = new VideoRequest(
                requestId: $request->requestId,
                source: $request->source,
                sourceId: $request->sourceId,
                title: $request->title,
                script: $request->script,
                aspectRatios: [$aspectRatio],
                durationSeconds: $request->durationSeconds,
                metadata: array_merge($request->metadata, [
                    'version_number' => $versionNumber,
                    'version_id' => sprintf('%s-V%d', $session->sessionId, $versionNumber),
                ]),
            );

            if (! $provider->supports($singleVersionRequest)) {
                throw new RuntimeException(sprintf('Video provider %s does not support aspect ratio %s.', $provider->providerId(), $aspectRatio));
            }

            $session->addVersion($provider->generate($singleVersionRequest, $session));
        }

        $session->status = 'generated';
        $session->metadata['provider'] = $provider->providerId();

        return $session;
    }

    private function resolveProvider(VideoRequest $request, ?string $providerId): VideoProvider
    {
        $providerId ??= (string) config('marketing_video.provider', 'gemini_veo');

        foreach ($this->providers as $provider) {
            if ($provider->providerId() === $providerId && $provider->supports($request)) {
                return $provider;
            }
        }

        if ($providerId === 'gemini_veo') {
            $provider = new GeminiVeoVideoProvider();
            if ($provider->supports($request)) {
                return $provider;
            }
        }

        throw new RuntimeException(sprintf('No compatible video provider is configured for %s.', $providerId));
    }
}
