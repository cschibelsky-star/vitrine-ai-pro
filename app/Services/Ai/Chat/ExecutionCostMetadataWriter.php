<?php

declare(strict_types=1);

namespace App\Services\Ai\Chat;

use App\Models\AiExecution;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Persists reconciled provider receipts onto an existing AI execution.
 * Per-execution idempotency only. This is NOT the shared spend ledger.
 */
final class ExecutionCostMetadataWriter
{
    public function persist(int $executionId, array $receipt): array
    {
        if ($executionId <= 0 || ($receipt['charge_authoritative'] ?? false) !== true
            || ($receipt['status'] ?? null) !== 'verified'
            || ! is_string($receipt['request_id'] ?? null)
            || trim($receipt['request_id']) === ''
            || ! is_int($receipt['actual_cost_minor'] ?? null)
            || $receipt['actual_cost_minor'] < 0
            || ! in_array($receipt['currency'] ?? null, ['BRL', 'USD'], true)) {
            throw new RuntimeException('Only verified provider receipts can be persisted.');
        }

        return DB::transaction(static function () use ($executionId, $receipt): array {
            /** @var AiExecution $execution */
            $execution = AiExecution::query()->whereKey($executionId)->lockForUpdate()->firstOrFail();
            $metadata = $execution->metadata ?? [];
            if (! is_array($metadata)) {
                throw new RuntimeException('Execution metadata must be an array.');
            }

            $existing = $metadata['verified_provider_cost'] ?? null;
            if ($existing !== null) {
                if (($existing['request_id'] ?? null) === $receipt['request_id']
                    && ($existing['actual_cost_minor'] ?? null) === $receipt['actual_cost_minor']
                    && ($existing['currency'] ?? null) === $receipt['currency']) {
                    return ['status' => 'already_recorded', 'execution_id' => $executionId];
                }
                throw new RuntimeException('Conflicting cost receipt already recorded on execution.');
            }

            $metadata['verified_provider_cost'] = [
                'request_id' => $receipt['request_id'],
                'provider' => $receipt['provider'] ?? null,
                'model_id' => $receipt['model_id'] ?? null,
                'scope' => $receipt['scope'] ?? null,
                'currency' => $receipt['currency'],
                'actual_cost_minor' => $receipt['actual_cost_minor'],
                'input_tokens' => $receipt['input_tokens'] ?? null,
                'output_tokens' => $receipt['output_tokens'] ?? null,
                'recorded_at' => now()->toIso8601String(),
            ];
            $execution->metadata = $metadata;
            $execution->save();

            return ['status' => 'recorded', 'execution_id' => $executionId];
        }, 3);
    }
}
