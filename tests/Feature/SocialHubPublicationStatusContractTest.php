<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SocialHubPublicationStatusContractTest extends TestCase
{
    private const KEY = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
    private const PROJECT = 'vitrine-ai-social-enterprise';
    private const ENDPOINT = '/api/internal/marketing/publisher/meta/publication-status';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Http::preventStrayRequests();
        Queue::fake();
        config()->set('marketing_publishing.enabled', false);
    }

    private function requestStatus(int $client = 10, int $piece = 20, string $key = self::KEY)
    {
        putenv('MARKETING_ENGINE_TOKEN=contract-test-only');
        try {
            return $this->withToken('contract-test-only')->postJson(self::ENDPOINT, [
                'project_id' => self::PROJECT,
                'client_id' => $client,
                'content_project_id' => $piece,
                'idempotency_key' => $key,
            ]);
        } finally {
            putenv('MARKETING_ENGINE_TOKEN');
        }
    }

    public function test_confirmed_status_is_read_only_when_publishing_disabled(): void
    {
        $identity = self::PROJECT.'|10|20';
        $path = 'marketing/publisher/native-ledger/'.hash('sha256', $identity).'.json';
        Storage::disk('local')->put($path, json_encode([
            'idempotency_key' => self::KEY,
            'status' => 'PUBLISHED',
            'channel' => 'instagram',
            'external_id' => 'test-provider-id-123',
        ], JSON_THROW_ON_ERROR));

        $this->requestStatus()->assertOk()->assertJson([
            'ok' => true,
            'status' => 'PUBLISHED',
            'idempotency_key' => self::KEY,
            'external_id' => 'test-provider-id-123',
        ]);
        $this->assertSame('PUBLISHED', json_decode(Storage::disk('local')->get($path), true)['status']);
        Http::assertNothingSent();
        Queue::assertNothingPushed();
    }

    public function test_other_client_or_piece_cannot_observe_entry(): void
    {
        $identity = self::PROJECT.'|10|20';
        Storage::disk('local')->put(
            'marketing/publisher/native-ledger/'.hash('sha256', $identity).'.json',
            json_encode([
                'idempotency_key' => self::KEY,
                'status' => 'PUBLISHED',
                'channel' => 'instagram',
                'external_id' => 'test-provider-id-123',
            ], JSON_THROW_ON_ERROR)
        );
        $this->requestStatus(11, 20)->assertOk()->assertJson(['ok' => false, 'status' => 'NOT_FOUND']);
        $this->requestStatus(10, 21)->assertOk()->assertJson(['ok' => false, 'status' => 'NOT_FOUND']);
        $this->requestStatus(10, 20, str_repeat('b', 64))
            ->assertOk()->assertJson(['ok' => false, 'status' => 'IDEMPOTENCY_CONFLICT']);
        Http::assertNothingSent();
        Queue::assertNothingPushed();
    }

    public function test_missing_token_is_rejected_without_external_effect(): void
    {
        putenv('MARKETING_ENGINE_TOKEN=contract-test-only');
        try {
            $this->postJson(self::ENDPOINT, [
                'project_id' => self::PROJECT,
                'client_id' => 10,
                'content_project_id' => 20,
                'idempotency_key' => self::KEY,
            ])->assertUnauthorized();
        } finally {
            putenv('MARKETING_ENGINE_TOKEN');
        }
        Http::assertNothingSent();
        Queue::assertNothingPushed();
    }

    public function test_invalid_identity_is_rejected_before_lookup(): void
    {
        putenv('MARKETING_ENGINE_TOKEN=contract-test-only');
        try {
            $this->withToken('contract-test-only')->postJson(self::ENDPOINT, [
                'project_id' => 'another-product',
                'client_id' => 10,
                'content_project_id' => 20,
                'idempotency_key' => self::KEY,
            ])->assertUnprocessable();
        } finally {
            putenv('MARKETING_ENGINE_TOKEN');
        }
        Http::assertNothingSent();
        Queue::assertNothingPushed();
    }
}
