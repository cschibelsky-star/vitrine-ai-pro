<?php

declare(strict_types=1);

namespace App\Factory\Engine\Services;

use App\Factory\Engine\Contracts\ProviderInterface;
use App\Factory\Engine\Providers\ClaudeProvider;
use App\Factory\Engine\Providers\GeminiProvider;
use App\Factory\Engine\Providers\InternalProvider;
use App\Factory\Engine\Providers\OpenAIProvider;
use App\Factory\Engine\Exceptions\ProviderException;

class ProviderManager
{
    public function provider(?string $name = null): ProviderInterface
    {
        $name ??= 'internal';

        $provider = match ($name) {
            'openai' => app(OpenAIProvider::class),
            'gemini' => app(GeminiProvider::class),
            'claude' => app(ClaudeProvider::class),
            'internal' => app(InternalProvider::class),
            default => throw new ProviderException("Provider não suportado: {$name}"),
        };

        if (! $provider->enabled()) {
            throw new ProviderException("Provider desabilitado: {$provider->name()}");
        }

        return $provider;
    }
}
