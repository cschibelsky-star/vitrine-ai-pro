<?php

declare(strict_types=1);

namespace App\Factory\Engine\Providers;

use App\Factory\Engine\Contracts\ProviderInterface;
use App\Factory\Engine\DTO\ProviderResponse;

class OpenAIProvider implements ProviderInterface
{
    public function generate(array $payload): ProviderResponse
    {
        return new ProviderResponse(
            provider: $this->name(),
            content: 'OpenAIProvider preparado. Integração externa será ativada em sprint posterior.',
            raw: ['payload' => $payload],
            usage: ['mode' => 'stub'],
        );
    }

    public function name(): string
    {
        return 'openai';
    }

    public function enabled(): bool
    {
        return (bool) config('factory_engine.providers.openai.enabled', false);
    }
}
