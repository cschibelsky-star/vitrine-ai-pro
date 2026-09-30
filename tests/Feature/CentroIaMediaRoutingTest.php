<?php

namespace Tests\Feature;

use App\Models\AiAgent;
use App\Models\AiMediaGeneration;
use App\Models\AiProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CentroIaMediaRoutingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => false,
        ]);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        Schema::connection('sqlite')->create('ai_providers', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status')->default('ativo');
            $table->text('api_key')->nullable();
            $table->json('config')->nullable();
            $table->timestamps();
        });

        Schema::connection('sqlite')->create('ai_agents', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('ai_provider_id')->nullable();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('model_name')->nullable();
            $table->string('status')->default('online');
            $table->json('config')->nullable();
            $table->timestamps();
        });

        Schema::connection('sqlite')->create('ai_media_generations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('ai_agent_id')->nullable();
            $table->unsignedBigInteger('ai_provider_id')->nullable();
            $table->string('capability', 80);
            $table->string('model_name', 160)->nullable();
            $table->string('status', 40)->default('Pendente');
            $table->longText('input');
            $table->longText('output')->nullable();
            $table->string('operation_id', 255)->nullable()->index();
            $table->string('asset_url', 2048)->nullable();
            $table->string('asset_path', 2048)->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            $table->index(['capability', 'status']);
        });

        config([
            'centro_ia.internal_token' => 'test-token',
            'centro_ia.capabilities.avatar_video' => [
                'agent_slug' => 'marketing-ia',
                'routing_capability' => 'avatar_video',
            ],
        ]);
        AiAgent::create(['name' => 'Marketing IA', 'slug' => 'marketing-ia']);
    }

    public function test_broker_falls_back_to_heygen_for_avatar_video_when_roteia_fails(): void
    {
        $this->setRuntimeEnv([
            'ROTEIA_API_KEY' => 'roteia-test-key',
            'ROTEIA_BASE_URL' => 'https://roteia.test',
            'HEYGEN_API_KEY' => 'heygen-test-key',
        ]);
        AiProvider::create([
            'name' => 'Roteia',
            'slug' => 'roteia',
            'status' => 'ativo',
            'config' => ['endpoints' => ['avatar_video' => 'v1/avatar']],
        ]);
        AiProvider::create(['name' => 'HeyGen', 'slug' => 'heygen', 'status' => 'ativo']);

        Http::fake([
            'https://roteia.test/v1/avatar' => Http::response(['error' => 'unavailable'], 503),
            'https://api.heygen.com/v3/video-agents' => Http::response([
                'data' => ['session_id' => 'session-123', 'status' => 'generating'],
            ], 201),
        ]);

        $response = $this->brokerRequest([
            'project_id' => 'test-project',
            'capability' => 'avatar_video',
            'input' => ['user' => 'Gere um vídeo com apresentador virtual.'],
        ]);

        $response->assertOk()
            ->assertJsonPath('provider', 'heygen')
            ->assertJsonPath('media_status', 'processing')
            ->assertJsonPath('job_ref', 'session-123')
            ->assertJsonPath('job_ref_type', 'session_id');
        Http::assertSent(fn ($request): bool => $request->url() === 'https://api.heygen.com/v3/video-agents');
    }

    public function test_broker_refreshes_heygen_session_and_returns_asset_url(): void
    {
        $this->setRuntimeEnv(['HEYGEN_API_KEY' => 'heygen-test-key']);
        AiProvider::create(['name' => 'HeyGen', 'slug' => 'heygen', 'status' => 'ativo']);
        Http::fake([
            'https://api.heygen.com/v3/video-agents' => Http::response([
                'data' => ['session_id' => 'session-456', 'status' => 'generating'],
            ], 201),
            'https://api.heygen.com/v3/video-agents/session-456' => Http::response([
                'data' => ['video_id' => 'video-456', 'status' => 'completed'],
            ]),
            'https://api.heygen.com/v3/videos/video-456' => Http::response([
                'data' => [
                    'status' => 'completed',
                    'video_url' => 'https://cdn.heygen.test/video-456.mp4',
                ],
            ]),
        ]);

        $created = $this->brokerRequest([
            'project_id' => 'test-project',
            'capability' => 'avatar_video',
            'input' => ['user' => 'Gere um vídeo com apresentador virtual.'],
        ])->assertOk();
        $jobRef = $created->json('job_ref');

        $this->brokerRequest([
            'project_id' => 'test-project',
            'capability' => 'avatar_video',
            'input' => [
                'user' => 'Atualize o vídeo do apresentador.',
                'operation' => 'refresh',
                'job_ref' => $jobRef,
            ],
        ])->assertOk()
            ->assertJsonPath('provider', 'heygen')
            ->assertJsonPath('media_status', 'completed')
            ->assertJsonPath('video_id', 'video-456')
            ->assertJsonPath('asset_url', 'https://cdn.heygen.test/video-456.mp4');
    }

    public function test_broker_refreshes_a_video_id_without_treating_it_as_a_session(): void
    {
        $this->setRuntimeEnv(['HEYGEN_API_KEY' => 'heygen-test-key']);
        AiProvider::create(['name' => 'HeyGen', 'slug' => 'heygen', 'status' => 'ativo']);
        Http::fake([
            'https://api.heygen.com/v3/video-agents' => Http::response([
                'data' => ['video_id' => 'video-only-789', 'status' => 'completed'],
            ], 201),
            'https://api.heygen.com/v3/videos/video-only-789' => Http::response([
                'data' => [
                    'status' => 'completed',
                    'video_url' => 'https://cdn.heygen.test/video-only-789.mp4',
                ],
            ]),
        ]);

        $created = $this->brokerRequest([
            'project_id' => 'test-project',
            'capability' => 'avatar_video',
            'input' => ['user' => 'Gere um vídeo com apresentador virtual.'],
        ])->assertOk()
            ->assertJsonPath('job_ref_type', 'video_id')
            ->assertJsonPath('video_id', 'video-only-789');
        $jobRef = $created->json('job_ref');

        $this->brokerRequest([
            'project_id' => 'test-project',
            'capability' => 'avatar_video',
            'input' => [
                'user' => 'Atualize o vídeo do apresentador.',
                'operation' => 'refresh',
                'job_ref' => $jobRef,
            ],
        ])->assertOk()
            ->assertJsonPath('media_status', 'completed')
            ->assertJsonPath('asset_url', 'https://cdn.heygen.test/video-only-789.mp4');

        Http::assertNotSent(fn ($request): bool => str_contains($request->url(), '/v3/video-agents/video-only-789'));
    }

    public function test_broker_refreshes_roteia_avatar_job_using_its_provider_and_normalizes_asset_url(): void
    {
        $this->setRuntimeEnv([
            'ROTEIA_API_KEY' => 'roteia-test-key',
            'ROTEIA_BASE_URL' => 'https://roteia.test',
        ]);
        AiProvider::create([
            'name' => 'Roteia',
            'slug' => 'roteia',
            'status' => 'ativo',
            'config' => ['endpoints' => ['avatar_video' => 'v1/avatar']],
        ]);
        AiMediaGeneration::create([
            'capability' => 'avatar_video',
            'status' => 'Pendente',
            'input' => 'Prompt de vídeo',
            'operation_id' => 'roteia-job-321',
            'metadata' => ['provider_slug' => 'roteia'],
        ]);
        Http::fake([
            'https://roteia.test/v1/generations/roteia-job-321' => Http::response([
                'data' => [
                    'status' => 'completed',
                    'asset_url' => '/media/roteia-job-321.mp4',
                ],
            ]),
        ]);

        $this->brokerRequest([
            'project_id' => 'test-project',
            'capability' => 'avatar_video',
            'input' => [
                'user' => 'Atualize o vídeo do apresentador.',
                'operation' => 'refresh',
                'job_ref' => 'roteia-job-321',
            ],
        ])->assertOk()
            ->assertJsonPath('provider', 'roteia')
            ->assertJsonPath('media_status', 'completed')
            ->assertJsonPath('asset_url', 'https://roteia.test/media/roteia-job-321.mp4');
    }

    private function brokerRequest(array $payload)
    {
        return $this->postJson('/api/internal/centro-ia/execute', $payload, [
            'Authorization' => 'Bearer test-token',
            'X-Vitrine-Project' => 'test-project',
        ]);
    }

    private function setRuntimeEnv(array $values): void
    {
        foreach ($values as $name => $value) {
            putenv($name.'='.$value);
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}
