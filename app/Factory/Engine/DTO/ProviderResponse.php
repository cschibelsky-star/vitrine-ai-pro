<?php

declare(strict_types=1);

namespace App\Factory\Engine\DTO;

final readonly class ProviderResponse
{
    public function __construct(
        public string $provider,
        public string $content,
        public array $raw = [],
        public array $usage = [],
    ) {
    }

    public function toArray(): array
    {
        return [
            'provider' => $this->provider,
            'content' => $this->content,
            'raw' => $this->raw,
            'usage' => $this->usage,
        ];
    }
}
