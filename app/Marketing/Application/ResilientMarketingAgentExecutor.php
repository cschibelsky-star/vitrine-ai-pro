<?php

declare(strict_types=1);

namespace App\Marketing\Application;

use Throwable;

final class ResilientMarketingAgentExecutor implements MarketingAgentExecutor
{
    /** @var array<string, array<string, mixed>> */
    private array $metadata = [];

    public function __construct(
        private readonly GeminiStrategyAgent $geminiStrategy,
        private readonly SimulatedMarketingAgentExecutor $simulated,
    ) {
    }

    public function execute(string $agentId, array $campaign, array $inputs): array
    {
        if ($agentId !== 'product_market_strategist' || ! $this->liveStrategyEnabled()) {
            $this->metadata[$agentId] = [
                'provider' => 'simulated',
                'fallback' => false,
            ];

            return $this->simulated->execute($agentId, $campaign, $inputs);
        }

        try {
            $output = $this->geminiStrategy->execute($campaign);
            $this->metadata[$agentId] = $this->geminiStrategy->metadata();

            return $output;
        } catch (Throwable $exception) {
            report($exception);

            $message = preg_replace('/AIza[0-9A-Za-z_-]+/', '[redacted]', $exception->getMessage()) ?: 'Gemini execution failed.';

            $this->metadata[$agentId] = [
                'provider' => 'simulated',
                'fallback' => true,
                'fallback_reason' => $exception::class,
                'fallback_message' => mb_substr($message, 0, 240),
            ];

            return $this->simulated->execute($agentId, $campaign, $inputs);
        }
    }

    public function metadataFor(string $agentId): array
    {
        return $this->metadata[$agentId] ?? [];
    }

    private function liveStrategyEnabled(): bool
    {
        return (bool) config('marketing_agents.gemini.strategy_enabled', false)
            && (string) config('marketing_agents.gemini.api_key') !== '';
    }
}
