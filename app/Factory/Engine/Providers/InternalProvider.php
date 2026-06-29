<?php

declare(strict_types=1);

namespace App\Factory\Engine\Providers;

use App\Factory\Engine\Contracts\ProviderInterface;
use App\Factory\Engine\DTO\ProviderResponse;

class InternalProvider implements ProviderInterface
{
    public function generate(array $payload): ProviderResponse
    {
        return new ProviderResponse(
            provider: $this->name(),
            content: 'Resposta simulada pelo InternalProvider do Factory Engine.',
            raw: [
                'payload' => $payload,
            ],
            usage: [
                'mode' => 'simulated',
            ],
        );
    }

    public function name(): string
    {
        return 'internal';
    }

    public function enabled(): bool
    {
        return true;
    }
}
