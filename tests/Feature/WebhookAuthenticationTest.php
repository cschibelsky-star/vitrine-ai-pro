<?php

namespace Tests\Feature;

use Tests\TestCase;

class WebhookAuthenticationTest extends TestCase
{
    public function test_asaas_webhook_fails_closed_without_token(): void
    {
        config(['webhooks.asaas.token' => null]);

        $this->postJson('/api/asaas/webhook', [])->assertUnauthorized();
    }

    public function test_asaas_webhook_rejects_invalid_token(): void
    {
        config(['webhooks.asaas.token' => 'expected-token']);

        $this->postJson('/api/asaas/webhook', [], [
            'asaas-access-token' => 'invalid-token',
        ])->assertUnauthorized();
    }

    public function test_heygen_webhook_fails_closed_without_token(): void
    {
        config(['webhooks.heygen.token' => null]);

        $this->postJson('/api/heygen/callback', [])->assertUnauthorized();
    }

    public function test_heygen_webhook_rejects_invalid_token(): void
    {
        config(['webhooks.heygen.token' => 'expected-token']);

        $this->postJson('/api/heygen/callback', [], [
            'x-heygen-webhook-token' => 'invalid-token',
        ])->assertUnauthorized();
    }
}
