<?php

declare(strict_types=1);

namespace App\Services\Ai\Chat;

/**
 * Read-only normalization layer for supplier price catalogs.
 * An advertised quote is never automatically marked verified or enabled.
 */
final class PriceQuoteRegistry
{
    /**
     * @param array<int, array<string,mixed>> $rows
     * @return array<int, array<string,mixed>>
     */
    public function normalize(array $rows, string $provider, string $currency, int $capturedAt): array
    {
        if (! in_array($provider, ['roteia', 'invision'], true)
            || ! in_array($currency, ['BRL', 'USD'], true)
            || $capturedAt <= 0) {
            return [];
        }

        $quotes = [];
        foreach ($rows as $row) {
            $id = trim((string) ($row['model_id'] ?? ''));
            $input = $row['input_per_million'] ?? null;
            $output = $row['output_per_million'] ?? null;
            if ($id === '' || ! is_numeric($input) || ! is_numeric($output)
                || ! is_finite((float) $input) || ! is_finite((float) $output)
                || (float) $input < 0 || (float) $output < 0) {
                continue;
            }
            $quotes[] = [
                'provider' => $provider,
                'model_id' => $id,
                'currency' => $currency,
                'input_per_million' => (float) $input,
                'output_per_million' => (float) $output,
                'captured_at' => $capturedAt,
                'verified' => false,
                'enabled' => false,
                'expires_at' => null,
                'quality_score' => null,
                'capabilities' => [],
            ];
        }

        return $quotes;
    }
}
