<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class ViaProductControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('vitrine_flow.token', 'test-via-token');
    }

    public function test_procurement_rejects_unsupported_task_with_validation_error(): void
    {
        $response = $this
            ->withToken('test-via-token')
            ->postJson('/api/flow/via/products/procurement/execute', [
                'task' => 'unsupported_task',
                'input' => [],
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['task']);
    }

    public function test_tv_digital_rejects_unsupported_task_with_validation_error(): void
    {
        $response = $this
            ->withToken('test-via-token')
            ->postJson('/api/flow/via/products/tv-digital/execute', [
                'task' => 'unsupported_task',
                'input' => [],
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['task']);
    }

    public function test_product_endpoints_reject_invalid_bearer_token(): void
    {
        $response = $this
            ->withToken('invalid-token')
            ->postJson('/api/flow/via/products/social-media/generate', [
                'briefing' => [],
            ]);

        $response
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Não autorizado.']);
    }
}
