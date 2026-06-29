<?php

declare(strict_types=1);

namespace App\Factory\Engine\Providers;

use App\Factory\Engine\Contracts\ProviderInterface;
use App\Factory\Engine\DTO\ProviderResponse;

class ClaudeProvider implements ProviderInterface
{
    public function generate(array $payload): ProviderResponse
    {
        return new ProviderResponse(
            provider: $this->name(),
            content: 'ClaudeProvider preparado. Integração externa será ativada em sprint posterior.',
            raw: ['payload' => $payload],
            usage: ['mode' => 'stub'],
        );
    }

    public function name(): string
    {
        return 'claude';
    }

    public function enabled(): bool
    {
        return (bool) config('factory_engine.providers.claude.enabled', false);
    }
}
