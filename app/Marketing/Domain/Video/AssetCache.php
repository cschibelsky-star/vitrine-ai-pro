<?php

declare(strict_types=1);

namespace App\Marketing\Domain\Video;

final class AssetCache
{
    /** @var array<string, array{ref:string, metadata:array<string, mixed>}> */
    private array $assets = [];

    /** @param array<string, mixed> $metadata */
    public function put(string $fingerprint, string $ref, array $metadata = []): void
    {
        $this->assets[$fingerprint] = [
            'ref' => $ref,
            'metadata' => $metadata,
        ];
    }

    public function has(string $fingerprint): bool
    {
        return isset($this->assets[$fingerprint]);
    }

    /** @return array{ref:string, metadata:array<string, mixed>}|null */
    public function get(string $fingerprint): ?array
    {
        return $this->assets[$fingerprint] ?? null;
    }
}
