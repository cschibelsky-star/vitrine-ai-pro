<?php

declare(strict_types=1);

namespace App\Shared\AI\Video;

final readonly class VideoVersion
{
    /** @param array<string, mixed> $metadata */
    public function __construct(
        public string $versionId,
        public string $sessionId,
        public int $number,
        public string $aspectRatio,
        public string $status = 'planned',
        public ?string $provider = null,
        public ?string $providerJobId = null,
        public ?string $videoUrl = null,
        public array $metadata = [],
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'version_id' => $this->versionId,
            'session_id' => $this->sessionId,
            'number' => $this->number,
            'aspect_ratio' => $this->aspectRatio,
            'status' => $this->status,
            'provider' => $this->provider,
            'provider_job_id' => $this->providerJobId,
            'video_url' => $this->videoUrl,
            'metadata' => $this->metadata,
        ];
    }
}
