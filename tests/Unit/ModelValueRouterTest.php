<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Ai\Chat\ModelValueRouter;
use PHPUnit\Framework\TestCase;

final class ModelValueRouterTest extends TestCase
{
    private function task(): array
    {
        return ['input_tokens' => 100000, 'output_tokens' => 10000,
            'budget' => 2.0, 'minimum_quality' => 0.8, 'currency' => 'USD',
            'capability' => 'coding', 'now' => 1000];
    }

    private function model(string $id, float $rate): array
    {
        return ['provider' => 'invision', 'model_id' => $id,
            'enabled' => true, 'verified' => true, 'currency' => 'USD',
            'capabilities' => ['coding'], 'input_per_million' => $rate,
            'output_per_million' => $rate, 'quality_score' => 0.9,
            'expires_at' => 2000];
    }

    public function test_chooses_lowest_eligible_cost(): void
    {
        $result = (new ModelValueRouter())->select(
            [$this->model('expensive', 4.0), $this->model('economical', 0.5)],
            $this->task()
        );
        self::assertSame('economical', $result['selected']['model_id']);
        self::assertSame(0.055, $result['selected']['estimated_cost']);
    }

    public function test_blocks_stale_unverified_and_foreign_currency_quotes(): void
    {
        $stale = $this->model('stale', 0.1);
        $stale['expires_at'] = 999;
        $unverified = $this->model('unverified', 0.1);
        $unverified['verified'] = false;
        $foreign = $this->model('foreign', 0.1);
        $foreign['currency'] = 'BRL';
        $result = (new ModelValueRouter())->select([$stale, $unverified, $foreign], $this->task());
        self::assertSame('no_eligible_verified_quote', $result['status']);
        self::assertNull($result['selected']);
    }

    public function test_budget_and_quality_floor_are_mandatory(): void
    {
        $lowQuality = $this->model('low-quality', 0.1);
        $lowQuality['quality_score'] = 0.2;
        $expensive = $this->model('over-budget', 50.0);
        $result = (new ModelValueRouter())->select([$lowQuality, $expensive], $this->task());
        self::assertNull($result['selected']);
    }
}
