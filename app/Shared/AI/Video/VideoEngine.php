<?php

declare(strict_types=1);

namespace App\Shared\AI\Video;

final class VideoEngine
{
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
}
