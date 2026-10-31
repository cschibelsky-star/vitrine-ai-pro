<?php

declare(strict_types=1);

namespace App\Services\Ai\Chat;

use InvalidArgumentException;

/**
 * Fail-closed preflight for AI requests. No API calls are made.
 * The caller must supply trusted provider token counts and verified pricing.
 */
final class RequestBudgetGuard
{
    /**
     * @param array<string,mixed> $request
     * @param array<string,mixed> $quote
     * @return array<string,mixed>
     */
    public function check(array $request, array $quote): array
    {
        $input = $request['input_tokens'] ?? null;
        $output = $request['max_output_tokens'] ?? null;
        $maxInput = $request['max_input_tokens'] ?? null;
        $remaining = $request['remaining_budget'] ?? null;
        $limit = $request['request_budget'] ?? null;
        $reserveFraction = $request['safety_margin_fraction'] ?? 0.15;
        $priceIn = $quote['input_per_million'] ?? null;
        $priceOut = $quote['output_per_million'] ?? null;

        foreach ([$input, $output, $maxInput] as $n) {
            if (! is_int($n) || $n < 0) {
                throw new InvalidArgumentException('Token counts and input limit must be nonnegative integers.');
            }
        }
        foreach ([$remaining, $limit, $reserveFraction, $priceIn, $priceOut] as $n) {
            if ((! is_int($n) && ! is_float($n)) || ! is_finite((float) $n) || $n < 0) {
                throw new InvalidArgumentException('Budget, margin and price must be nonnegative numeric values.');
            }
        }
        if ($reserveFraction >= 1 || ! is_int($quote['expires_at'] ?? null)
            || $quote['expires_at'] <= time()
            || ($quote['verified'] ?? false) !== true
            || ($quote['enabled'] ?? false) !== true
            || ! is_string($quote['currency'] ?? null)
            || $quote['currency'] !== ($request['currency'] ?? null)) {
            return ['allowed' => false, 'reason' => 'unverified_or_incompatible_quote'];
        }
        if ($input > $maxInput) {
            return ['allowed' => false, 'reason' => 'input_context_exceeds_limit',
                'input_tokens' => $input, 'max_input_tokens' => $maxInput];
        }

        $base = (($input * $priceIn) + ($output * $priceOut)) / 1000000;
        $reservedCost = $base / (1 - $reserveFraction);
        if ($reservedCost > min($remaining, $limit)) {
            return ['allowed' => false, 'reason' => 'budget_insufficient',
                'estimated_max_cost' => round($reservedCost, 8), 'currency' => $quote['currency']];
        }
        return ['allowed' => true, 'reason' => 'preflight_passed',
            'estimated_max_cost' => round($reservedCost, 8), 'currency' => $quote['currency']];
    }
}
