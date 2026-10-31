<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Ai\Chat\RequestBudgetGuard;
use PHPUnit\Framework\TestCase;

final class RequestBudgetGuardTest extends TestCase
{
    private function quote(): array
    {
        return ['input_per_million' => 10.0, 'output_per_million' => 10.0,
            'currency' => 'BRL', 'verified' => true, 'enabled' => true,
            'expires_at' => time() + 3600];
    }

    private function request(): array
    {
        return ['input_tokens' => 572000, 'max_output_tokens' => 20000,
            'max_input_tokens' => 100000, 'remaining_budget' => 6.94,
            'request_budget' => 6.94, 'safety_margin_fraction' => 0.15,
            'currency' => 'BRL'];
    }

    public function test_blocks_excessive_context_before_provider_call(): void
    {
        $result = (new RequestBudgetGuard())->check($this->request(), $this->quote());
        self::assertFalse($result['allowed']);
        self::assertSame('input_context_exceeds_limit', $result['reason']);
    }

    public function test_blocks_over_budget_request(): void
    {
        $request = $this->request();
        $request['max_input_tokens'] = 700000;
        $result = (new RequestBudgetGuard())->check($request, $this->quote());
        self::assertFalse($result['allowed']);
        self::assertSame('budget_insufficient', $result['reason']);
    }

    public function test_fails_closed_when_quote_is_unverified(): void
    {
        $quote = $this->quote();
        $quote['verified'] = false;
        self::assertSame('unverified_or_incompatible_quote',
            (new RequestBudgetGuard())->check($this->request(), $quote)['reason']);
    }

    public function test_allows_small_request_with_headroom(): void
    {
        $request = $this->request();
        $request['input_tokens'] = 2000;
        $request['max_output_tokens'] = 500;
        $result = (new RequestBudgetGuard())->check($request, $this->quote());
        self::assertTrue($result['allowed']);
        self::assertGreaterThan(0, $result['estimated_max_cost']);
    }
}
