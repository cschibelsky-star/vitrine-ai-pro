<?php

declare(strict_types=1);

namespace App\Shared\AI\Video;

final readonly class VideoRequest
{
    /**
     * @param list<string> $aspectRatios
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $requestId,
        public string $source,
        public string $sourceId,
        public string $title,
        public string $script,
        public array $aspectRatios = ['16:9'],
        public ?int $durationSeconds = null,
        public array $metadata = [],
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'request_id' => $this->requestId,
            'source' => $this->source,
            'source_id' => $this->sourceId,
            'title' => $this->title,
            'script' => $this->script,
            'aspect_ratios' => $this->aspectRatios,
            'duration_seconds' => $this->durationSeconds,
            'metadata' => $this->metadata,
        ];
    }
}
