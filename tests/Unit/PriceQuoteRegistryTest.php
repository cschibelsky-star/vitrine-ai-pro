<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Ai\Chat\PriceQuoteRegistry;
use PHPUnit\Framework\TestCase;

final class PriceQuoteRegistryTest extends TestCase
{
    public function test_unverified_quotes_cannot_be_selected_for_execution(): void
    {
        $quotes = (new PriceQuoteRegistry())->normalize([
            ['model_id' => 'anthropic/claude-sonnet-5.5',
                'input_per_million' => '1.56', 'output_per_million' => '6.15'],
        ], 'invision', 'USD', 1792875600);
        self::assertCount(1, $quotes);
        self::assertFalse($quotes[0]['verified']);
        self::assertFalse($quotes[0]['enabled']);
        self::assertNull($quotes[0]['expires_at']);
    }

    public function test_invalid_costs_and_unknown_providers_are_rejected(): void
    {
        $registry = new PriceQuoteRegistry();
        $quotes = $registry->normalize([
            ['model_id' => 'bad', 'input_per_million' => -1, 'output_per_million' => 2],
            ['model_id' => 'bad2', 'input_per_million' => 1, 'output_per_million' => 'abc'],
            ['model_id' => 'ok', 'input_per_million' => 0.1, 'output_per_million' => 0.2],
        ], 'roteia', 'BRL', 1792875600);
        self::assertCount(1, $quotes);
        self::assertSame('ok', $quotes[0]['model_id']);
        self::assertSame('BRL', $quotes[0]['currency']);
        self::assertSame([], $registry->normalize([], 'unknown', 'USD', 1792875600));
    }
}
