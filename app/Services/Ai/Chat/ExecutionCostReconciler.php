<?php

declare(strict_types=1);

namespace App\Services\Ai\Chat;

use InvalidArgumentException;

/**
 * Converts trusted provider usage and billing into an auditable cost entry.
 *
 * Pure mapping only: persistence and idempotent database writes belong to the
 * existing Centro IA execution/accounting service, never this class.
 */
final class ExecutionCostReconciler
{
    /**
     * @param array<string, mixed> $usage
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function reconcile(array $usage, array $context): array
    {
        $requestId = $context['request_id'] ?? null;
        $provider = $context['provider'] ?? null;
        $model = $context['model_id'] ?? null;
        $currency = $context['currency'] ?? null;
        $scope = $context['scope'] ?? null;
        $inputTokens = $usage['input_tokens'] ?? null;
        $outputTokens = $usage['output_tokens'] ?? null;
        $actualMinor = $usage['actual_cost_minor'] ?? null;
        $verified = $usage['verified_provider_receipt'] ?? false;

        foreach ([$requestId, $provider, $model, $currency, $scope] as $value) {
            if (! is_string($value) || trim($value) === '') {
                throw new InvalidArgumentException('Missing cost attribution metadata.');
            }
        }
        if (! in_array($currency, ['USD', 'BRL'], true)) {
            throw new InvalidArgumentException('Unsupported billing currency.');
        }
        foreach ([$inputTokens, $outputTokens] as $tokens) {
            if (! is_int($tokens) || $tokens < 0) {
                throw new InvalidArgumentException('Provider token usage is required.');
            }
        }
        if (! is_int($actualMinor) || $actualMinor < 0 || $verified !== true) {
            return [
                'status' => 'pending_provider_reconciliation',
                'request_id' => $requestId,
                'charge_authoritative' => false,
            ];
        }

        return [
            'status' => 'verified',
            'request_id' => $requestId,
            'provider' => $provider,
            'model_id' => $model,
            'scope' => $scope,
            'currency' => $currency,
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'actual_cost_minor' => $actualMinor,
            'charge_authoritative' => true,
        ];
    }
}
