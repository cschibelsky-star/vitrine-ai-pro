<?php

declare(strict_types=1);

namespace App\Factory\Engine\DTO;

final readonly class ExecutionOutput
{
    public function __construct(
        public bool $success,
        public array $output = [],
        public ?string $message = null,
        public array $metadata = [],
    ) {
    }

    public static function success(array $output, ?string $message = null, array $metadata = []): self
    {
        return new self(true, $output, $message, $metadata);
    }

    public static function failure(string $message, array $metadata = []): self
    {
        return new self(false, [], $message, $metadata);
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'output' => $this->output,
            'message' => $this->message,
            'metadata' => $this->metadata,
        ];
    }
}
