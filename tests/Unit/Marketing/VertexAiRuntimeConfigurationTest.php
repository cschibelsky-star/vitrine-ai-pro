<?php

namespace Tests\Unit\Marketing;

use App\Services\Ai\AiRoutingService;
use ReflectionMethod;
use Tests\TestCase;

class VertexAiRuntimeConfigurationTest extends TestCase
{
    private array $originalEnvironment = [];

    private ?string $credentialsFile = null;

    private const ENVIRONMENT_KEYS = [
        'VERTEX_AI_ENABLED',
        'GOOGLE_CLOUD_PROJECT',
        'GOOGLE_APPLICATION_CREDENTIALS',
        'GOOGLE_VERTEX_ACCESS_TOKEN',
        'VERTEX_AI_DAILY_REQUEST_LIMIT',
        'GOOGLE_VERTEX_VIDEO_GCS_URI',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        foreach (self::ENVIRONMENT_KEYS as $key) {
            $value = getenv($key);
            $this->originalEnvironment[$key] = $value === false ? null : $value;
        }

        $this->credentialsFile = tempnam(sys_get_temp_dir(), 'vertex-ai-adc-');
        file_put_contents($this->credentialsFile, '{"type":"service_account"}');

        $this->setVertexEnvironment([
            'VERTEX_AI_ENABLED' => 'true',
            'GOOGLE_CLOUD_PROJECT' => 'test-project',
            'GOOGLE_APPLICATION_CREDENTIALS' => $this->credentialsFile,
            'GOOGLE_VERTEX_ACCESS_TOKEN' => '',
            'VERTEX_AI_DAILY_REQUEST_LIMIT' => '5',
            'GOOGLE_VERTEX_VIDEO_GCS_URI' => '',
        ]);
    }

    protected function tearDown(): void
    {
        foreach ($this->originalEnvironment as $key => $value) {
            if ($value === null) {
                putenv($key);
                unset($_ENV[$key], $_SERVER[$key]);
            } else {
                putenv($key.'='.$value);
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }

        if ($this->credentialsFile !== null && is_file($this->credentialsFile)) {
            unlink($this->credentialsFile);
        }

        parent::tearDown();
    }

    public function test_vertex_is_not_configured_when_explicitly_disabled(): void
    {
        $this->setVertexEnvironment(['VERTEX_AI_ENABLED' => 'false']);

        $this->assertFalse($this->vertexConfigured('image_generation'));
    }

    public function test_vertex_is_incomplete_when_daily_limit_is_zero(): void
    {
        $this->setVertexEnvironment(['VERTEX_AI_DAILY_REQUEST_LIMIT' => '0']);

        $this->assertFalse($this->vertexConfigured('image_generation'));
    }

    public function test_enabled_image_generation_accepts_readable_adc_without_static_token(): void
    {
        $this->assertSame('', getenv('GOOGLE_VERTEX_ACCESS_TOKEN'));

        $this->assertTrue($this->vertexConfigured('image_generation'));
    }

    public function test_video_generation_requires_a_gcs_destination(): void
    {
        $this->assertFalse($this->vertexConfigured('video_generation'));

        $this->setVertexEnvironment([
            'GOOGLE_VERTEX_VIDEO_GCS_URI' => 'gs://vertex-video-output',
        ]);

        $this->assertTrue($this->vertexConfigured('video_generation'));
    }

    public function test_daily_limit_is_declared_in_the_env_template_and_passed_to_hml(): void
    {
        $envExample = file_get_contents(base_path('.env.example'));
        $compose = file_get_contents(base_path('docker-compose.core-hml.yml'));

        $this->assertStringContainsString('VERTEX_AI_DAILY_REQUEST_LIMIT=0', $envExample);
        $this->assertStringContainsString('VERTEX_AI_DAILY_REQUEST_LIMIT: "${VERTEX_AI_DAILY_REQUEST_LIMIT:-0}"', $compose);
    }

    private function vertexConfigured(string $capability): bool
    {
        $service = (new \ReflectionClass(AiRoutingService::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod(AiRoutingService::class, 'vertexAiRuntimeConfigured');
        $method->setAccessible(true);

        return $method->invoke($service, $capability);
    }

    private function setVertexEnvironment(array $values): void
    {
        foreach ($values as $key => $value) {
            putenv($key.'='.$value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}
