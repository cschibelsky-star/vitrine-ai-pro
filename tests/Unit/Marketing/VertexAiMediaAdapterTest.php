<?php

namespace Tests\Unit\Marketing;

use App\Services\Ai\VertexAiCredentials;
use App\Services\Ai\VertexAiDailyQuota;
use App\Services\Ai\VertexAiMediaAdapter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class VertexAiMediaAdapterTest extends TestCase
{
    private array $saved = [];
    private array $temporaryFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        Storage::fake('local');
        config(['filesystems.default' => 'local']);
        $this->environment([
            'VERTEX_AI_ENABLED' => 'true',
            'GOOGLE_CLOUD_PROJECT' => 'test-project',
            'GOOGLE_CLOUD_LOCATION' => 'us-central1',
            'GOOGLE_VERTEX_ACCESS_TOKEN' => 'test-only-token',
            'GOOGLE_APPLICATION_CREDENTIALS' => '',
            'VERTEX_AI_DAILY_REQUEST_LIMIT' => '1',
            'GOOGLE_VERTEX_VIDEO_GCS_URI' => '',
        ]);
        (require base_path('database/migrations/2026_10_03_160000_create_vertex_ai_daily_usage_table.php'))->up();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('vertex_ai_daily_usage');
        foreach ($this->saved as $key => [$process, $env, $server]) {
            $process === false ? putenv($key) : putenv($key.'='.$process);
            if ($env === null) {
                unset($_ENV[$key]);
            } else {
                $_ENV[$key] = $env;
            }
            if ($server === null) {
                unset($_SERVER[$key]);
            } else {
                $_SERVER[$key] = $server;
            }
        }
        foreach ($this->temporaryFiles as $path) {
            unlink($path);
        }
        parent::tearDown();
    }

    public function test_image_dispatch_persists_asset_and_rejects_second_request_at_limit(): void
    {
        Http::fake(['*aiplatform.googleapis.com/*' => Http::response([
            'predictions' => [['bytesBase64Encoded' => base64_encode('test image'), 'mimeType' => 'image/png']],
        ])]);
        $adapter = app(VertexAiMediaAdapter::class);
        $result = $adapter->generate('image_generation', 'Test', 'imagen-4.0-generate-001');
        $this->assertSame('Concluído', $result['status']);
        Storage::disk('local')->assertExists($result['asset_path']);
        try {
            $adapter->generate('image_generation', 'Second', 'imagen-4.0-generate-001');
            $this->fail('Daily quota must block the second request.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Limite diário Vertex atingido.', $exception->getMessage());
        }
        Http::assertSentCount(1);
        $this->assertSame(1, (int) DB::table('vertex_ai_daily_usage')->value('requests'));
    }

    public function test_disabled_runtime_makes_no_http_requests_and_reserves_nothing(): void
    {
        $this->environment(['VERTEX_AI_ENABLED' => 'false']);
        try {
            app(VertexAiMediaAdapter::class)->generate('image_generation', 'Test');
            $this->fail('Disabled Vertex must be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Vertex desabilitado.', $exception->getMessage());
        }
        Http::assertNothingSent();
        $this->assertSame(0, DB::table('vertex_ai_daily_usage')->count());
    }

    public function test_video_requires_gcs_and_polls_existing_operation_without_regeneration(): void
    {
        $adapter = app(VertexAiMediaAdapter::class);
        try {
            $adapter->generate('video_generation', 'Test', 'veo-3.0-generate-001');
            $this->fail('GCS must be required.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('GCS', $exception->getMessage());
        }
        Http::assertNothingSent();
        $this->environment(['GOOGLE_VERTEX_VIDEO_GCS_URI' => 'gs://test-video-output']);
        $operation = 'projects/test-project/locations/us-central1/publishers/google/models/veo-3.0-generate-001/operations/test-operation';
        Http::fake([
            '*:predictLongRunning' => Http::response(['name' => $operation]),
            '*:fetchPredictOperation' => Http::response([
                'done' => true,
                'response' => ['videos' => [['gcsUri' => 'gs://test-video-output/video.mp4']]],
            ]),
        ]);
        $started = $adapter->generate('video_generation', 'Test', 'veo-3.0-generate-001');
        $this->assertSame('Processando', $started['status']);
        $this->assertSame($operation, $started['operation_id']);
        $finished = $adapter->poll($operation, 'veo-3.0-generate-001');
        $this->assertSame('Concluído', $finished['status']);
        $this->assertSame('gs://test-video-output/video.mp4', $finished['metadata']['vertex_gcs_uri']);
        $this->assertArrayNotHasKey('asset_url', $finished);
        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), ':predictLongRunning')
            && $request['parameters']['sampleCount'] === 1
            && $request['parameters']['durationSeconds'] === 8
            && $request['parameters']['generateAudio'] === false
            && $request['parameters']['storageUri'] === 'gs://test-video-output');
        $this->assertSame(1, (int) DB::table('vertex_ai_daily_usage')->value('requests'));
    }

    public function test_failed_generation_keeps_reservation_and_does_not_leak_response(): void
    {
        Http::fake(['*' => Http::response('sensitive-provider-response', 503)]);
        try {
            app(VertexAiMediaAdapter::class)->generate('image_generation', 'Test');
            $this->fail('Provider failure must be reported.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Vertex retornou HTTP 503.', $exception->getMessage());
        }
        Http::assertSentCount(1);
        $this->assertSame(1, (int) DB::table('vertex_ai_daily_usage')->value('requests'));
    }

    public function test_quota_is_shared_by_instances_and_isolated_by_project_and_utc_day(): void
    {
        (new VertexAiDailyQuota)->reserve('project-a', 1);
        (new VertexAiDailyQuota)->reserve('project-b', 1);
        $this->travel(1)->days();
        try {
            (new VertexAiDailyQuota)->reserve('project-a', 1);
            $this->assertSame(3, DB::table('vertex_ai_daily_usage')->count());
        } finally {
            $this->travelBack();
        }
    }

    public function test_invalid_credential_type_is_rejected_before_any_network_call(): void
    {
        $path = $this->credentialFile(['type' => 'external_account', 'token_url' => 'https://invalid.example']);
        $this->environment(['GOOGLE_VERTEX_ACCESS_TOKEN' => '', 'GOOGLE_APPLICATION_CREDENTIALS' => $path]);
        try {
            app(VertexAiCredentials::class)->accessToken();
            $this->fail('Unsupported credential type must be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('conta de serviço', $exception->getMessage());
        }
        Http::assertNothingSent();
    }

    public function test_service_account_gets_temporary_token_with_verified_rs256_signature(): void
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($key, $privateKey);
        $publicKey = openssl_pkey_get_details($key)['key'];
        $path = $this->credentialFile([
            'type' => 'service_account', 'client_email' => 'fixture@test-project.iam.gserviceaccount.com',
            'private_key' => $privateKey, 'token_uri' => 'https://invalid.example/not-used',
        ]);
        $this->environment(['GOOGLE_VERTEX_ACCESS_TOKEN' => '', 'GOOGLE_APPLICATION_CREDENTIALS' => $path]);
        Http::fake(['https://oauth2.googleapis.com/token' => Http::response([
            'access_token' => 'temporary-test-token', 'expires_in' => 3600,
        ])]);
        $this->assertSame('temporary-test-token', app(VertexAiCredentials::class)->accessToken());
        Http::assertSent(function ($request) use ($publicKey) {
            [$header, $claims, $signature] = explode('.', $request['assertion']);
            $decode = fn ($part) => base64_decode(strtr($part, '-_', '+/'));
            $payload = json_decode($decode($claims), true);
            return $request->url() === 'https://oauth2.googleapis.com/token'
                && openssl_verify($header.'.'.$claims, $decode($signature), $publicKey, OPENSSL_ALGO_SHA256) === 1
                && $payload['aud'] === 'https://oauth2.googleapis.com/token'
                && $payload['scope'] === 'https://www.googleapis.com/auth/cloud-platform'
                && $payload['exp'] - $payload['iat'] === 3600;
        });
        Http::assertSentCount(1);
    }

    public function test_polling_persists_completion_without_resubmitting_generation(): void
    {
        Schema::create('ai_media_generations', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->string('status');
            $table->string('capability');
            $table->string('model_name');
            $table->text('operation_id');
            $table->json('metadata');
            $table->text('output')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
        try {
            $operation = 'projects/test-project/locations/us-central1/publishers/google/models/veo-3.0-generate-001/operations/fixture';
            $generation = \App\Models\AiMediaGeneration::create([
                'status' => 'Processando', 'capability' => 'video_generation',
                'model_name' => 'veo-3.0-generate-001', 'operation_id' => $operation,
                'metadata' => ['provider_slug' => 'vertex-ai'],
            ]);
            Http::fake(['*:fetchPredictOperation' => Http::response([
                'done' => true, 'response' => ['videos' => [['gcsUri' => 'gs://test-video-output/video.mp4']]],
            ])]);
            $service = app(\App\Services\Ai\AiMediaGenerationService::class);
            $finished = $service->refreshVertexVideo($generation);
            $this->assertSame('Concluído', $finished->status);
            $this->assertNotNull($finished->finished_at);
            $this->assertSame('vertex-ai', $finished->metadata['provider_slug']);
            $this->assertSame('gs://test-video-output/video.mp4', $finished->metadata['vertex_gcs_uri']);
            $service->refreshVertexVideo($finished);
            Http::assertSentCount(1);
            $this->assertSame(0, DB::table('vertex_ai_daily_usage')->count());
        } finally {
            Schema::dropIfExists('ai_media_generations');
        }
    }

    private function credentialFile(array $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'vertex-test-');
        file_put_contents($path, json_encode($contents));
        $this->temporaryFiles[] = $path;
        return $path;
    }

    private function environment(array $values): void
    {
        foreach ($values as $key => $value) {
            if (! array_key_exists($key, $this->saved)) {
                $this->saved[$key] = [getenv($key), $_ENV[$key] ?? null, $_SERVER[$key] ?? null];
            }
            putenv($key.'='.$value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}
