<?php

declare(strict_types=1);

namespace App\Shared\AI\Video;

final class VideoSession
{
    /**
     * @param list<VideoVersion> $versions
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $sessionId,
        public string $requestId,
        public string $status = 'planned',
        public array $versions = [],
        public array $metadata = [],
    ) {
    }

    public function addVersion(VideoVersion $version): void
    {
        $this->versions[] = $version;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'session_id' => $this->sessionId,
            'request_id' => $this->requestId,
            'status' => $this->status,
            'versions' => array_map(static fn (VideoVersion $version): array => $version->toArray(), $this->versions),
            'metadata' => $this->metadata,
        ];
    }
}
