<?php

namespace Tests\Feature\Shared\AI;

use App\Shared\AI\Models\AiConsumer;
use App\Shared\AI\Models\AiConsumption;
use App\Shared\AI\Services\AiUsageLedger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiUsageLedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_registers_cost_by_consumer_gateway_and_model(): void
    {
        $usage = app(AiUsageLedger::class)->record([
            'consumer_key' => 'marketing_ia',
            'consumer_name' => 'Marketing IA',
            'gateway' => 'openrouter',
            'model_name' => 'openai/gpt-4o',
            'capability' => 'text',
            'request_id' => 'req-test-1',
            'quantity' => 1,
            'input_units' => 1000,
            'output_units' => 250,
            'original_cost' => 0.01,
            'original_currency' => 'USD',
            'fx_rate' => 5.50,
            'status' => 'completed',
        ]);

        $this->assertInstanceOf(AiConsumption::class, $usage);
        $this->assertSame('marketing_ia', AiConsumer::findOrFail($usage->ai_consumer_id)->key);
        $this->assertSame('openrouter', $usage->gateway);
        $this->assertSame('openai/gpt-4o', $usage->model_name);
        $this->assertSame('0.055000', $usage->cost_brl);
        $this->assertSame(now()->format('Y-m'), $usage->billing_period);
    }
}
