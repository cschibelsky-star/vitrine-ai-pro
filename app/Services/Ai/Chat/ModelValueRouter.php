<?php

declare(strict_types=1);

namespace App\Services\Ai\Chat;

use InvalidArgumentException;

/**
 * Deterministic, side-effect-free decision policy.
 * Quotes must be server-verified, fresh and expressed in the same currency.
 * No external calls or spending are performed here.
 */
final class ModelValueRouter
{
    /**
     * @param array<int, array<string, mixed>> $candidates
     * @param array<string, mixed> $task
     * @return array<string, mixed>
     */
    public function select(array $candidates, array $task): array
    {
        $input = $this->nonNegativeInt($task['input_tokens'] ?? null);
        $output = $this->nonNegativeInt($task['output_tokens'] ?? null);
        $budget = $this->nonNegativeNumber($task['budget'] ?? null);
        $qualityFloor = $this->nonNegativeNumber($task['minimum_quality'] ?? null);
        $currency = strtoupper((string) ($task['currency'] ?? ''));
        $capability = (string) ($task['capability'] ?? '');
        $now = (int) ($task['now'] ?? time());

        if ($currency === '' || $capability === '' || $input === null || $output === null || $budget === null || $qualityFloor === null) {
            throw new InvalidArgumentException('Task needs valid currency, capability, token estimates, budget and quality floor.');
        }

        $eligible = [];
        foreach ($candidates as $candidate) {
            $rateIn = $this->nonNegativeNumber($candidate['input_per_million'] ?? null);
            $rateOut = $this->nonNegativeNumber($candidate['output_per_million'] ?? null);
            $quality = $this->nonNegativeNumber($candidate['quality_score'] ?? null);
            $expires = filter_var($candidate['expires_at'] ?? null, FILTER_VALIDATE_INT);
            $capabilities = $candidate['capabilities'] ?? [];
            if (($candidate['enabled'] ?? false) !== true
                || ($candidate['verified'] ?? false) !== true
                || strtoupper((string) ($candidate['currency'] ?? '')) !== $currency
                || ! is_array($capabilities) || ! in_array($capability, $capabilities, true)
                || $rateIn === null || $rateOut === null || $quality === null
                || $quality < $qualityFloor || $expires === false || $expires <= $now
                || trim((string) ($candidate['model_id'] ?? '')) === ''
                || trim((string) ($candidate['provider'] ?? '')) === ''
            ) {
                continue;
            }

            $estimatedCost = (($input * $rateIn) + ($output * $rateOut)) / 1000000;
            if ($estimatedCost > $budget) {
                continue;
            }
            $eligible[] = [
                'provider' => $candidate['provider'],
                'model_id' => $candidate['model_id'],
                'currency' => $currency,
                'estimated_cost' => round($estimatedCost, 8),
                'quality_score' => $quality,
                'reason' => 'Verified and unexpired quote; capability and quality floor satisfied; lowest projected token cost.',
            ];
        }
        usort($eligible, fn (array $a, array $b): int =>
            ($a['estimated_cost'] <=> $b['estimated_cost'])
            ?: ($b['quality_score'] <=> $a['quality_score'])
            ?: strcmp((string) $a['model_id'], (string) $b['model_id']));
        return ['selected' => $eligible[0] ?? null, 'eligible_count' => count($eligible),
            'status' => $eligible === [] ? 'no_eligible_verified_quote' : 'selected'];
    }

    private function nonNegativeNumber(mixed $value): ?float
    {
        return (is_int($value) || is_float($value)) && is_finite((float) $value) && $value >= 0
            ? (float) $value : null;
    }

    private function nonNegativeInt(mixed $value): ?int
    {
        return is_int($value) && $value >= 0 ? $value : null;
    }
}
