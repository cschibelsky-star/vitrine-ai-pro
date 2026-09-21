<?php

declare(strict_types=1);

namespace App\Shared\AI\Services;

use App\Shared\AI\Models\AiConsumption;
use App\Shared\AI\Models\AiConsumer;
use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

final class AiUsageLedger
{
    /**
     * Register one billable or measurable AI usage event.
     *
     * @param array<string,mixed> $event
     */
    public function record(array $event): AiConsumption
    {
        $occurredAt = $event['occurred_at'] ?? now();
        if (! $occurredAt instanceof CarbonInterface) {
            $occurredAt = now()->parse((string) $occurredAt);
        }

        $consumerKey = trim((string) ($event['consumer_key'] ?? 'core'));
        $consumer = AiConsumer::firstOrCreate(
            ['key' => $consumerKey],
            [
                'name' => (string) ($event['consumer_name'] ?? Str::headline($consumerKey)),
                'type' => (string) ($event['consumer_type'] ?? 'product'),
                'status' => 'active',
            ],
        );

        $originalCost = array_key_exists('original_cost', $event)
            ? (float) $event['original_cost']
            : null;
        $fxRate = array_key_exists('fx_rate', $event)
            ? (float) $event['fx_rate']
            : null;
        $costBrl = array_key_exists('cost_brl', $event)
            ? (float) $event['cost_brl']
            : (($originalCost !== null && $fxRate !== null) ? $originalCost * $fxRate : null);

        return AiConsumption::create([
            'ai_consumer_id' => $consumer->id,
            'company_id' => $event['company_id'] ?? null,
            'product_id' => $event['product_id'] ?? null,
            'license_id' => $event['license_id'] ?? null,
            'ai_agent_id' => $event['ai_agent_id'] ?? null,
            'ai_provider_id' => $event['ai_provider_id'] ?? null,
            'gateway' => $event['gateway'] ?? null,
            'model_name' => $event['model_name'] ?? null,
            'capability' => $event['capability'] ?? ($event['resource_type'] ?? 'execution'),
            'request_id' => $event['request_id'] ?? null,
            'resource_type' => $event['resource_type'] ?? 'execution',
            'unit_type' => $event['unit_type'] ?? null,
            'quantity' => $event['quantity'] ?? 1,
            'input_units' => $event['input_units'] ?? 0,
            'output_units' => $event['output_units'] ?? 0,
            'estimated_cost' => $event['estimated_cost'] ?? 0,
            'actual_cost' => $event['actual_cost'] ?? $costBrl,
            'original_cost' => $originalCost,
            'original_currency' => strtoupper((string) ($event['original_currency'] ?? 'BRL')),
            'fx_rate' => $fxRate,
            'cost_brl' => $costBrl,
            'status' => $event['status'] ?? 'completed',
            'latency_ms' => $event['latency_ms'] ?? null,
            'billing_period' => $occurredAt->format('Y-m'),
            'consumption_date' => $occurredAt->toDateString(),
            'occurred_at' => $occurredAt,
            'notes' => $event['notes'] ?? null,
            'metadata' => Arr::except($event, [
                'consumer_key',
                'consumer_name',
                'consumer_type',
                'company_id',
                'product_id',
                'license_id',
                'ai_agent_id',
                'ai_provider_id',
                'gateway',
                'model_name',
                'capability',
                'request_id',
                'resource_type',
                'unit_type',
                'quantity',
                'input_units',
                'output_units',
                'estimated_cost',
                'actual_cost',
                'original_cost',
                'original_currency',
                'fx_rate',
                'cost_brl',
                'status',
                'latency_ms',
                'occurred_at',
                'notes',
            ]),
        ]);
    }
}
