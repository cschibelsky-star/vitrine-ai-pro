<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\AiAgent;
use App\Models\AiExecution;
use App\Services\Ai\AiRoutingService;
use App\Services\Ai\Chat\AiChatOrchestrator;
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
        (new AiChatOrchestrator($routing))->send(new AiAgent(), '   ');
    }

    public function test_rejects_unsupported_capability(): void
    {
        $routing = Mockery::mock(AiRoutingService::class);
        $routing->shouldNotReceive('execute');
        $this->expectException(InvalidArgumentException::class);
        (new AiChatOrchestrator($routing))->send(new AiAgent(), 'hello', 'image_generation');
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
        $result = (new AiChatOrchestrator($routing))->send($agent, ' hello ');
        $this->assertTrue($result['ok']);
        $this->assertSame('Test response', $result['output_text']);
        $this->assertSame(81, $result['execution_id']);
    }

    public function test_compare_requires_two_to_three_agents(): void
    {
        $routing = Mockery::mock(AiRoutingService::class);
        $routing->shouldNotReceive('execute');
        $this->expectException(InvalidArgumentException::class);
        (new AiChatOrchestrator($routing))->compare(['one' => new AiAgent()], 'hello');
    }
}
