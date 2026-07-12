<?php

namespace App\Services;

use App\Factory\Engine\DTO\ProviderResponse;
use App\Factory\Engine\Services\ProviderManager;
use App\Models\AiProvider;
use Throwable;

class FlowAiRouterService
{
    public function __construct(
        private readonly ProviderManager $providers,
    ) {
    }

    public function route(array $payload, array $options = []): array
    {
        $order = $this->resolveProviderOrder($options);
        $attempts = [];

        foreach ($order as $providerName) {
            try {
                $provider = $this->providers->provider($providerName);
                $startedAt = microtime(true);
                $response = $provider->generate($payload);

                return [
                    'ok' => true,
                    'selected_provider' => $response->provider,
                    'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                    'response' => $response->toArray(),
                    'attempts' => array_merge($attempts, [[
                        'provider' => $providerName,
                        'status' => 'success',
                    ]]),
                ];
            } catch (Throwable $exception) {
                $attempts[] = [
                    'provider' => $providerName,
                    'status' => 'failed',
                    'error' => $exception->getMessage(),
                ];
            }
        }

        return [
            'ok' => false,
            'selected_provider' => null,
            'response' => null,
            'attempts' => $attempts,
            'message' => 'Nenhum provider disponível concluiu a solicitação.',
        ];
    }

    private function resolveProviderOrder(array $options): array
    {
        $requested = array_values(array_filter(array_unique(array_map(
            static fn ($value) => strtolower(trim((string) $value)),
            $options['providers'] ?? [],
        ))));

        if ($requested !== []) {
            return $requested;
        }

        $configured = AiProvider::query()
            ->where('status', 'active')
            ->orderBy('priority')
            ->pluck('name')
            ->filter()
            ->map(static fn ($name) => strtolower((string) $name))
            ->values()
            ->all();

        return $configured !== []
            ? $configured
            : ['gemini', 'openai', 'claude', 'internal'];
    }
}
