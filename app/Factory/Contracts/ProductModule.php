<?php

declare(strict_types=1);

namespace App\Factory\Contracts;

interface ProductModule
{
    public function key(): string;

    public function name(): string;

    public function version(): string;

    /** @return array<string, mixed> */
    public function dependencies(): array;

    /** @return array<string, mixed> */
    public function permissions(): array;

    /** @return array<string, mixed> */
    public function health(): array;

    public function install(): void;

    public function update(): void;

    public function rollback(): void;
}
