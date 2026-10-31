<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\AiAgent;
use App\Models\AiExecution;
use App\Services\Ai\AiRoutingService;
use App\Services\Ai\Chat\AiChatOrchestrator;
use App\Services\Ai\Chat\RequestBudgetGuard;
use InvalidArgumentException;
use Mockery;
use Tests\TestCase;

class AiChatOrchestratorTest extends TestCase
{
    public function test_rejects_empty_prompt_without_calling_provider(): void
    {
        $routing = Mockery::mock(AiRoutingService::class);
        $routing->shouldNotReceive('execute');
        $this->expectException(InvalidArgumentException::class);
        (new AiChatOrchestrator($routing, new RequestBudgetGuard()))->send(new AiAgent(), '   ');
    }

    public function test_rejects_unsupported_capability(): void
    {
        $routing = Mockery::mock(AiRoutingService::class);
        $routing->shouldNotReceive('execute');
        $this->expectException(InvalidArgumentException::class);
        (new AiChatOrchestrator($routing, new RequestBudgetGuard()))->send(new AiAgent(), 'hello', 'image_generation');
    }

    public function test_dispatches_to_existing_routing_service(): void
    {
        $agent = new AiAgent();
        $execution = new AiExecution();
        $execution->id = 81;
        $execution->status = 'Concluído';
        $execution->output = 'Test response';

        $routing = Mockery::mock(AiRoutingService::class);
        $routing->shouldReceive('execute')->once()->with($agent, 'hello', 'copy')->andReturn($execution);
        $quote = ['input_per_million' => 0.5, 'output_per_million' => 1.0,
            'currency' => 'BRL', 'verified' => true, 'enabled' => true,
            'expires_at' => time() + 3600];
        $budget = ['input_tokens' => 50, 'max_output_tokens' => 100,
            'max_input_tokens' => 1000, 'remaining_budget' => 1.0,
            'request_budget' => 1.0, 'currency' => 'BRL'];
        $result = (new AiChatOrchestrator($routing, new RequestBudgetGuard()))->send($agent, ' hello ', 'copy', $budget, $quote);
        $this->assertTrue($result['ok']);
        $this->assertSame('Test response', $result['output_text']);
        $this->assertSame(81, $result['execution_id']);
    }

    public function test_missing_budget_blocks_before_provider_call(): void
    {
        $routing = Mockery::mock(AiRoutingService::class);
        $routing->shouldNotReceive('execute');
        $result = (new AiChatOrchestrator($routing, new RequestBudgetGuard()))->send(new AiAgent(), 'hello');
        $this->assertFalse($result['ok']);
        $this->assertSame('budget_or_quote_missing', $result['reason']);
    }

    public function test_compare_requires_two_to_three_agents(): void
    {
        $routing = Mockery::mock(AiRoutingService::class);
        $routing->shouldNotReceive('execute');
        $this->expectException(InvalidArgumentException::class);
        (new AiChatOrchestrator($routing, new RequestBudgetGuard()))->compare(['one' => new AiAgent()], 'hello');
    }
}
