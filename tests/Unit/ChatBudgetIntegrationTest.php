<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\AiAgent;
use App\Services\Ai\AiRoutingService;
use App\Services\Ai\Chat\AiChatOrchestrator;
use App\Services\Ai\Chat\RequestBudgetGuard;
use Mockery;
use Tests\TestCase;

/** Zero external requests: regression tests for pre-dispatch spending controls. */
final class ChatBudgetIntegrationTest extends TestCase
{
    private function quote(): array
    {
        return ['input_per_million' => 10.0, 'output_per_million' => 10.0,
            'currency' => 'BRL', 'verified' => true, 'enabled' => true,
            'expires_at' => time() + 3600];
    }

    private function budget(): array
    {
        return ['input_tokens' => 572000, 'max_output_tokens' => 20000,
            'max_input_tokens' => 100000, 'remaining_budget' => 6.94,
            'request_budget' => 6.94, 'currency' => 'BRL'];
    }

    public function test_oversized_context_never_reaches_routing_service(): void
    {
        $routing = Mockery::mock(AiRoutingService::class);
        $routing->shouldNotReceive('execute');
        $result = (new AiChatOrchestrator($routing, new RequestBudgetGuard()))
            ->send(new AiAgent(), 'Review a code excerpt', 'copy', $this->budget(), $this->quote());
        self::assertSame('blocked_preflight', $result['status']);
        self::assertSame('input_context_exceeds_limit', $result['reason']);
    }

    public function test_insufficient_balance_never_reaches_routing_service(): void
    {
        $routing = Mockery::mock(AiRoutingService::class);
        $routing->shouldNotReceive('execute');
        $budget = $this->budget();
        $budget['max_input_tokens'] = 600000;
        $result = (new AiChatOrchestrator($routing, new RequestBudgetGuard()))
            ->send(new AiAgent(), 'Review a code excerpt', 'copy', $budget, $this->quote());
        self::assertSame('budget_insufficient', $result['reason']);
    }

    public function test_comparison_stops_after_first_block_and_does_not_fan_out(): void
    {
        $routing = Mockery::mock(AiRoutingService::class);
        $routing->shouldNotReceive('execute');
        $result = (new AiChatOrchestrator($routing, new RequestBudgetGuard()))
            ->compare(['first' => new AiAgent(), 'second' => new AiAgent()], 'hello', $this->budget(), $this->quote());
        self::assertCount(1, $result);
        self::assertFalse($result['first']['ok']);
    }
}
