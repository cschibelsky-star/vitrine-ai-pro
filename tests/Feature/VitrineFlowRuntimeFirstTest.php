<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\FlowWorkflow;
use App\Models\Payment;
use App\Services\FlowRuntimeService;
use App\Services\VitrineFlowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class VitrineFlowRuntimeFirstTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_canonical_workflow_uses_runtime(): void
    {
        config()->set('vitrine_flow.prefer_runtime', true);
        config()->set('vitrine_flow.legacy_fallback', true);
        config()->set('vitrine_flow.provision_workflow_key', 'provision_product');

        $company = Company::query()->create(['nome' => 'Empresa Flow Teste']);
        $payment = Payment::query()->create([
            'company_id' => $company->getKey(),
            'descricao' => 'Provisionamento',
            'valor' => 100,
            'vencimento' => now()->addDay()->toDateString(),
            'status' => 'Pago',
        ]);
        $workflow = FlowWorkflow::query()->create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $company->getKey(),
            'workflow_key' => 'provision_product',
            'name' => 'Provisionar Produto',
            'version' => '1.0.0',
            'status' => 'active',
            'is_active' => true,
        ]);

        $runtime = Mockery::mock(FlowRuntimeService::class);
        $runtime->shouldReceive('start')
            ->once()
            ->withArgs(function (FlowWorkflow $receivedWorkflow, array $input, array $options) use ($workflow, $payment): bool {
                return $receivedWorkflow->is($workflow)
                    && $input['event'] === 'payment.approved'
                    && $options['company_id'] === $payment->company_id
                    && Str::isUuid($options['correlation_id']);
            })
            ->andReturn((object) ['uuid' => (string) Str::uuid()]);

        $result = (new VitrineFlowService($runtime))->dispatchProvisioning($payment);

        $this->assertTrue($result['accepted']);
        $this->assertSame('runtime', $result['mode']);
        $this->assertSame($workflow->uuid, $result['workflow_uuid']);
    }

    public function test_missing_workflow_uses_legacy_fallback(): void
    {
        config()->set('vitrine_flow.prefer_runtime', true);
        config()->set('vitrine_flow.legacy_fallback', true);
        config()->set('vitrine_flow.base_url', 'https://flow.test');
        config()->set('vitrine_flow.provision_webhook', '/webhook/factory/provision');

        $company = Company::query()->create(['nome' => 'Empresa Legacy Teste']);
        $payment = Payment::query()->create([
            'company_id' => $company->getKey(),
            'descricao' => 'Provisionamento',
            'valor' => 100,
            'vencimento' => now()->addDay()->toDateString(),
            'status' => 'Pago',
        ]);

        Http::fake([
            'https://flow.test/webhook/factory/provision' => Http::response(['accepted' => true], 200),
        ]);

        $runtime = Mockery::mock(FlowRuntimeService::class);
        $runtime->shouldNotReceive('start');

        $result = (new VitrineFlowService($runtime))->dispatchProvisioning($payment);

        $this->assertSame('legacy', $result['mode']);
        Http::assertSentCount(1);
    }

    public function test_missing_workflow_and_disabled_fallback_fails_safely(): void
    {
        config()->set('vitrine_flow.prefer_runtime', true);
        config()->set('vitrine_flow.legacy_fallback', false);

        $company = Company::query()->create(['nome' => 'Empresa Sem Fallback']);
        $payment = Payment::query()->create([
            'company_id' => $company->getKey(),
            'descricao' => 'Provisionamento',
            'valor' => 100,
            'vencimento' => now()->addDay()->toDateString(),
            'status' => 'Pago',
        ]);

        $runtime = Mockery::mock(FlowRuntimeService::class);
        $runtime->shouldNotReceive('start');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Workflow canônico de provisionamento não está registrado e ativo.');

        (new VitrineFlowService($runtime))->dispatchProvisioning($payment);
    }
}
