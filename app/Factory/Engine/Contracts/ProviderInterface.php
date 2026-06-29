<?php

declare(strict_types=1);

namespace App\Factory\Engine\Contracts;

use App\Factory\Engine\DTO\ProviderResponse;

interface ProviderInterface
{
    public function generate(array $payload): ProviderResponse;

    public function name(): string;

    public function enabled(): bool;
}
