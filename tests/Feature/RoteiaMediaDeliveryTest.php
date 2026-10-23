<?php

namespace Tests\Feature;

use App\Models\AiProvider;
use App\Services\Ai\AiMediaGenerationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RoteiaMediaDeliveryTest extends TestCase
{
    private function dispatchPayload(
        array $payload,
        int $status = 200,
        array $headers = ['x-request-id' => 'billing-id'],
        array $providerConfig = ['endpoints' => ['image_generation' => 'images']],
        string $baseUrl = 'https://roteia.example',
    ): array
    {
        config(['filesystems.default' => 'local']);
        Storage::fake('local');
        putenv('ROTEIA_API_KEY=test-only-key');
        putenv('ROTEIA_BASE_URL='.$baseUrl);
        Http::preventStrayRequests();
        Http::fake([parse_url($baseUrl, PHP_URL_HOST).'/*' => Http::response($payload, $status, $headers)]);
        $provider = new AiProvider(['slug' => 'roteia', 'config' => $providerConfig]);
        $service = new class extends AiMediaGenerationService {
            public function run(AiProvider $provider): array
            {
                return $this->generateRoteiaMedia($provider, 'image_generation', 'Test image', 'test-model');
            }
        };

        try {
            return $service->run($provider);
        } finally {
            putenv('ROTEIA_API_KEY');
            putenv('ROTEIA_BASE_URL');
        }
    }

    public function test_default_image_endpoint_matches_roteia_api_contract(): void
    {
        $png = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aS1sAAAAASUVORK5CYII=';
        $result = $this->dispatchPayload(
            ['data' => [['b64_json' => $png]]],
            200,
            [],
            [],
            'https://api.roteia.ai/v1',
        );

        $this->assertSame('Concluído', $result['status']);
        Storage::disk('local')->assertExists($result['asset_path']);
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) =>
            $request->url() === 'https://api.roteia.ai/v1/images/generations'
            && $request['model'] === 'test-model'
            && $request['prompt'] === 'Test image'
            && ! isset($request['capability'])
        );
    }

    public function test_legacy_v1_path_does_not_duplicate_api_version(): void
    {
        $result = $this->dispatchPayload(
            ['status' => 'pending', 'job_id' => 'job-one'],
            200,
            [],
            ['endpoints' => ['image_generation' => '/v1/images/generations']],
            'https://api.roteia.ai/v1',
        );

        $this->assertSame('Pendente', $result['status']);
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) =>
            $request->url() === 'https://api.roteia.ai/v1/images/generations'
        );
    }

    public function test_completed_without_image_is_error_and_keeps_request_id(): void
    {
        $result = $this->dispatchPayload(['status' => 'completed', 'job_id' => 'job-id']);
        $this->assertSame('Erro', $result['status']);
        $this->assertSame('billing-id', $result['metadata']['provider_request_id']);
        $this->assertSame('job-id', $result['operation_id']);
        $this->assertFalse($result['metadata']['generation_retry_allowed']);
        Http::assertSentCount(1);
    }

    public function test_remote_url_does_not_prove_local_delivery(): void
    {
        $result = $this->dispatchPayload(['status' => 'completed', 'url' => 'https://images.example/image.png']);
        $this->assertSame('Erro', $result['status']);
        $this->assertStringContainsString('asset_hosts', $result['error_message']);
        Http::assertSentCount(1);
    }

    public function test_invalid_base64_is_not_completed(): void
    {
        $result = $this->dispatchPayload(['status' => 'completed', 'request_id' => 'payload-id', 'data' => [['b64_json' => base64_encode('not an image')]]]);
        $this->assertSame('Erro', $result['status']);
        $this->assertSame('payload-id', $result['metadata']['provider_request_id']);
        Http::assertSentCount(1);
    }

    public function test_valid_image_is_saved_before_completion(): void
    {
        $png = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aS1sAAAAASUVORK5CYII=';
        $result = $this->dispatchPayload(['data' => [['b64_json' => $png]]]);
        $this->assertSame('Concluído', $result['status']);
        Storage::disk('local')->assertExists($result['asset_path']);
        $this->assertSame('asset_saved', $result['metadata']['phase']);
        Http::assertSentCount(1);
    }

    public function test_payload_id_is_used_when_request_header_is_absent(): void
    {
        $result = $this->dispatchPayload(['status' => 'completed', 'id' => 'payload-only-id'], 200, []);
        $this->assertSame('payload-only-id', $result['metadata']['provider_request_id']);
        $this->assertSame('Erro', $result['status']);
        Http::assertSentCount(1);
    }

    public function test_http_error_is_not_retried_and_keeps_request_id(): void
    {
        $result = $this->dispatchPayload([], 500);
        $this->assertSame('Erro', $result['status']);
        $this->assertSame('billing-id', $result['metadata']['provider_request_id']);
        Http::assertSentCount(1);
    }
}
