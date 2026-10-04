<?php

namespace Tests\Feature\Shared\AI;

use App\Shared\AI\Models\AiConsumer;
use App\Shared\AI\Models\AiConsumption;
use App\Shared\AI\Services\AiUsageLedger;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AiUsageLedgerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('ai_consumers', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('type')->nullable();
            $table->string('status')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_consumptions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('ai_consumer_id');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('license_id')->nullable();
            $table->unsignedBigInteger('ai_agent_id')->nullable();
            $table->unsignedBigInteger('ai_provider_id')->nullable();
            $table->string('gateway')->nullable();
            $table->string('model_name')->nullable();
            $table->string('capability')->nullable();
            $table->string('request_id')->nullable();
            $table->string('resource_type')->nullable();
            $table->string('unit_type')->nullable();
            $table->decimal('quantity', 18, 4)->default(1);
            $table->decimal('input_units', 18, 4)->default(0);
            $table->decimal('output_units', 18, 4)->default(0);
            $table->decimal('estimated_cost', 18, 4)->default(0);
            $table->decimal('actual_cost', 18, 6)->nullable();
            $table->decimal('original_cost', 18, 6)->nullable();
            $table->string('original_currency', 8)->nullable();
            $table->decimal('fx_rate', 18, 6)->nullable();
            $table->decimal('cost_brl', 18, 6)->nullable();
            $table->string('status')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->string('billing_period', 7);
            $table->date('consumption_date');
            $table->dateTime('occurred_at');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('ai_consumptions');
        Schema::dropIfExists('ai_consumers');

        parent::tearDown();
    }

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
