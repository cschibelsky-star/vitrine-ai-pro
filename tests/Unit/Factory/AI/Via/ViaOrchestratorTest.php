<?php

namespace Tests\Unit\Factory\AI\Via;

use App\Factory\AI\Via\Services\ViaOrchestrator;
use App\Services\FlowAiRouterService;
use PHPUnit\Framework\TestCase;

class ViaOrchestratorTest extends TestCase
{
    public function test_it_enriches_request_and_preserves_operational_context(): void
    {
        $router = $this->createMock(FlowAiRouterService::class);

        $router->expects($this->once())
            ->method('route')
            ->with(
                $this->callback(function (array $payload): bool {
                    return $payload['prompt'] === 'Gerar conteúdo'
                        && $payload['via_context']['company_id'] === 10
                        && $payload['via_context']['user_id'] === 20
                        && $payload['via_context']['product'] === 'social-midia'
                        && $payload['via_context']['session_id'] === 'sessao-123'
                        && $payload['via_context']['workflow_uuid'] === '11111111-1111-4111-8111-111111111111'
                        && $payload['via_context']['execution_uuid'] === '22222222-2222-4222-8222-222222222222'
                        && $payload['via_context']['trace_id'] === '33333333-3333-4333-8333-333333333333'
                        && $payload['via_context']['correlation_id'] === '44444444-4444-4444-8444-444444444444';
                }),
                $this->callback(function (array $options): bool {
                    return $options['providers'] === ['gemini', 'openai']
                        && $options['company_id'] === 10
                        && $options['workflow_uuid'] === '11111111-1111-4111-8111-111111111111'
                        && $options['execution_uuid'] === '22222222-2222-4222-8222-222222222222'
                        && $options['trace_id'] === '33333333-3333-4333-8333-333333333333'
                        && $options['correlation_id'] === '44444444-4444-4444-8444-444444444444'
                        && $options['metadata'] === ['channel' => 'api'];
                }),
            )
            ->willReturn([
                'ok' => true,
                'selected_provider' => 'gemini',
                'response' => ['text' => 'Conteúdo gerado'],
                'attempts' => [],
            ]);

        $via = new ViaOrchestrator($router);

        $result = $via->handle(
            ['prompt' => 'Gerar conteúdo'],
            [
                'providers' => ['gemini', 'openai'],
                'company_id' => 10,
                'user_id' => 20,
                'product' => 'social-midia',
                'session_id' => 'sessao-123',
                'workflow_uuid' => '11111111-1111-4111-8111-111111111111',
                'execution_uuid' => '22222222-2222-4222-8222-222222222222',
                'trace_id' => '33333333-3333-4333-8333-333333333333',
                'correlation_id' => '44444444-4444-4444-8444-444444444444',
                'metadata' => ['channel' => 'api'],
            ],
        );

        $this->assertTrue($result['ok']);
        $this->assertSame('gemini', $result['selected_provider']);
        $this->assertSame('33333333-3333-4333-8333-333333333333', $result['via']['trace_id']);
        $this->assertSame('44444444-4444-4444-8444-444444444444', $result['via']['correlation_id']);
        $this->assertSame('social-midia', $result['via']['product']);
        $this->assertSame('sessao-123', $result['via']['session_id']);
    }
}
