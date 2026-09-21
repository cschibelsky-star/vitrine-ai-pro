<?php

declare(strict_types=1);

namespace App\Shared\AI\Services;

use Throwable;

final class AiUsageTelemetry
{
    public function __construct(
        private readonly AiUsageLedger $ledger,
    ) {
    }

    /**
     * @param array<string,mixed> $payload
     * @param array<string,mixed> $context
     */
    public function recordOpenAiCompatible(
        string $gateway,
        ?int $providerId,
        ?int $agentId,
        string $consumerKey,
        string $model,
        string $capability,
        array $payload,
        int $latencyMs,
        string $status = 'completed',
        array $context = [],
    ): void {
        try {
            $usage = (array) ($payload['usage'] ?? []);
            $inputUnits = $this->numeric(
                $usage['prompt_tokens']
                ?? $usage['input_tokens']
                ?? $usage['promptTokens']
                ?? 0
            ) ?? 0.0;
            $outputUnits = $this->numeric(
                $usage['completion_tokens']
                ?? $usage['output_tokens']
                ?? $usage['completionTokens']
                ?? 0
            ) ?? 0.0;

            $cost = $this->firstNumeric([
                data_get($usage, 'cost'),
                data_get($usage, 'total_cost'),
                data_get($payload, 'cost'),
                data_get($payload, 'total_cost'),
                data_get($payload, 'billing.cost'),
                data_get($payload, 'billing.cost_brl'),
            ]);

            $currency = strtolower($gateway) === 'roteia' ? 'BRL' : 'USD';
            $fxRate = $currency === 'BRL'
                ? 1.0
                : $this->numeric(config('centro_ia.cost_center.usd_brl_rate'));

            $servedModel = trim((string) (
                $payload['model']
                ?? data_get($payload, 'data.model')
                ?? $model
            ));

            $requestId = trim((string) (
                $payload['id']
                ?? data_get($payload, 'request_id')
                ?? data_get($payload, 'data.id')
                ?? ''
            ));

            $this->ledger->record([
                'consumer_key' => $consumerKey !== '' ? $consumerKey : 'core',
                'consumer_name' => $context['consumer_name'] ?? null,
                'consumer_type' => $context['consumer_type'] ?? 'product',
                'company_id' => $context['company_id'] ?? null,
                'product_id' => $context['product_id'] ?? null,
                'license_id' => $context['license_id'] ?? null,
                'ai_agent_id' => $agentId,
                'ai_provider_id' => $providerId,
                'gateway' => strtolower($gateway),
                'model_name' => $servedModel !== '' ? $servedModel : $model,
                'capability' => $capability,
                'request_id' => $requestId !== '' ? $requestId : null,
                'resource_type' => 'texto',
                'unit_type' => 'tokens',
                'quantity' => max(1, $inputUnits + $outputUnits),
                'input_units' => $inputUnits,
                'output_units' => $outputUnits,
                'estimated_cost' => 0,
                'actual_cost' => $currency === 'BRL' ? $cost : (($cost !== null && $fxRate !== null) ? $cost * $fxRate : null),
                'original_cost' => $cost,
                'original_currency' => $currency,
                'fx_rate' => $fxRate,
                'cost_brl' => $currency === 'BRL' ? $cost : (($cost !== null && $fxRate !== null) ? $cost * $fxRate : null),
                'status' => $status,
                'latency_ms' => $latencyMs,
                'occurred_at' => now(),
                'cost_source' => $cost !== null ? 'provider_response' : 'provider_pending',
                'provider_usage' => $usage,
            ]);
        } catch (Throwable) {
            // Telemetria nunca pode interromper a execução principal.
        }
    }

    /**
     * @param array<string,mixed> $payload
     * @param array<string,mixed> $context
     */
    public function recordMedia(
        string $gateway,
        ?int $providerId,
        ?int $agentId,
        string $consumerKey,
        string $model,
        string $capability,
        array $payload,
        int $latencyMs,
        string $status,
        array $context = [],
    ): void {
        try {
            $cost = $this->firstNumeric([
                data_get($payload, 'usage.cost'),
                data_get($payload, 'usage.total_cost'),
                data_get($payload, 'cost'),
                data_get($payload, 'total_cost'),
                data_get($payload, 'billing.cost'),
                data_get($payload, 'billing.cost_brl'),
            ]);

            $currency = strtolower($gateway) === 'roteia' ? 'BRL' : 'USD';
            $fxRate = $currency === 'BRL'
                ? 1.0
                : $this->numeric(config('centro_ia.cost_center.usd_brl_rate'));

            $duration = $this->numeric(
                $context['duration_seconds']
                ?? data_get($payload, 'duration_seconds')
                ?? data_get($payload, 'duration')
            );

            $requestId = trim((string) (
                $payload['id']
                ?? $payload['request_id']
                ?? $payload['job_id']
                ?? $payload['operation_id']
                ?? $context['request_id']
                ?? ''
            ));

            $isVideo = str_contains(strtolower($capability), 'video');

            $this->ledger->record([
                'consumer_key' => $consumerKey !== '' ? $consumerKey : 'core',
                'consumer_name' => $context['consumer_name'] ?? null,
                'consumer_type' => $context['consumer_type'] ?? 'product',
                'company_id' => $context['company_id'] ?? null,
                'product_id' => $context['product_id'] ?? null,
                'license_id' => $context['license_id'] ?? null,
                'ai_agent_id' => $agentId,
                'ai_provider_id' => $providerId,
                'gateway' => strtolower($gateway),
                'model_name' => $model,
                'capability' => $capability,
                'request_id' => $requestId !== '' ? $requestId : null,
                'resource_type' => $isVideo ? 'video' : 'imagem',
                'unit_type' => $isVideo ? 'seconds' : 'images',
                'quantity' => $isVideo && $duration !== null ? max(1, $duration) : 1,
                'input_units' => 0,
                'output_units' => 0,
                'estimated_cost' => 0,
                'actual_cost' => $currency === 'BRL' ? $cost : (($cost !== null && $fxRate !== null) ? $cost * $fxRate : null),
                'original_cost' => $cost,
                'original_currency' => $currency,
                'fx_rate' => $fxRate,
                'cost_brl' => $currency === 'BRL' ? $cost : (($cost !== null && $fxRate !== null) ? $cost * $fxRate : null),
                'status' => $status,
                'latency_ms' => $latencyMs,
                'occurred_at' => now(),
                'cost_source' => $cost !== null ? 'provider_response' : 'provider_pending',
                'provider_usage' => $payload['usage'] ?? null,
            ]);
        } catch (Throwable) {
            // Telemetria nunca pode interromper a execução principal.
        }
    }

    /** @param array<int,mixed> $values */
    private function firstNumeric(array $values): ?float
    {
        foreach ($values as $value) {
            $numeric = $this->numeric($value);
            if ($numeric !== null) {
                return $numeric;
            }
        }

        return null;
    }

    private function numeric(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }
}
