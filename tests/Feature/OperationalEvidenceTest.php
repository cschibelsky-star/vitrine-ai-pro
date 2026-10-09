<?php

namespace Tests\Feature;

use App\Services\Deploy\OperationalEvidence;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

final class OperationalEvidenceTest extends TestCase
{
    private string $directory;
    private string $secret;
    private array $record;
    private bool $skipStep = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir().'/collector-http-'.bin2hex(random_bytes(8));
        mkdir($this->directory, 0700);
        $keys = sodium_crypto_sign_keypair();
        $this->secret = sodium_crypto_sign_secretkey($keys);
        config(['publication.evidence_directory' => $this->directory,
            'publication.collector_public_key' => base64_encode(sodium_crypto_sign_publickey($keys)),
            'publication.github_token' => 'test-token',
            'homologation_projects.projects' => [[
                'id' => 'conheca-sumare', 'repository' => 'cschibelsky-star/vitrine-ai-pro',
                'candidate_ref' => 'candidate', 'hml_url' => 'https://localhost', 'production_url' => 'https://prod.example',
                'executor' => 'conheca-sumare-vps', 'required_workflows' => ['qa.yml'], 'required_steps' => ['qa.yml' => ['Acceptance tests']],
            ]]]);
        $this->record = [
            'project_id' => 'conheca-sumare', 'repository' => 'cschibelsky-star/vitrine-ai-pro',
            'hml_url' => 'https://localhost', 'production_url' => 'https://prod.example',
            'executor' => 'conheca-sumare-vps', 'observed_at' => time(),
            'hml' => ['container_id' => 'hml', 'running' => true, 'data_sources' => ['/hml/data'],
                'secret_sources' => ['/hml/secrets'], 'networks' => ['hml'], 'image_revision' => str_repeat('a', 40),
                'checkout_sha' => str_repeat('a', 40), 'checkout_clean' => true, 'repository' => 'cschibelsky-star/vitrine-ai-pro'],
            'production' => ['container_id' => 'prod', 'running' => true, 'data_sources' => ['/prod/data'],
                'secret_sources' => ['/prod/secrets'], 'networks' => ['prod']],
            'backup' => ['archive_valid' => true, 'sha256' => str_repeat('c', 64), 'observed_at' => time()],
            'rollback' => ['backup_sha256' => str_repeat('c', 64), 'script_sha256' => str_repeat('d', 64)],
        ];
        Http::fake([
            '*commits*' => Http::response(['sha' => str_repeat('a', 40)]),
            '*actions/runs/1/jobs*' => fn () => Http::response(['total_count' => 1, 'jobs' => [[
                'status' => 'completed', 'conclusion' => 'success',
                'steps' => [['name' => 'Acceptance tests', 'status' => 'completed', 'conclusion' => $this->skipStep ? 'skipped' : 'success']],
            ]]]),
            '*actions/runs*' => Http::response(['workflow_runs' => [[
                'id' => 1, 'path' => 'qa.yml', 'head_sha' => str_repeat('a', 40),
                'head_repository' => ['full_name' => 'cschibelsky-star/vitrine-ai-pro'], 'status' => 'completed', 'conclusion' => 'success',
            ]]]),
            'https://localhost' => Http::response('healthy'),
        ]);
    }

    private function measure(): array
    {
        $payload = json_encode($this->record, JSON_THROW_ON_ERROR);
        file_put_contents($this->directory.'/conheca-sumare.json', json_encode([
            'payload' => base64_encode($payload), 'signature' => base64_encode(sodium_crypto_sign_detached($payload, $this->secret)),
        ]));
        return app(OperationalEvidence::class)->collect('conheca-sumare', str_repeat('a', 40))['checks'];
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory.'/*') ?: [] as $file) { unlink($file); }
        rmdir($this->directory);
        parent::tearDown();
    }

    public function test_measured_identity_and_successful_test_steps_are_required(): void
    {
        $checks = $this->measure();
        $this->assertTrue($checks['ci_green']);
        $this->assertTrue($checks['isolated_data']);
        $this->assertSame(str_repeat('a', 40), $checks['tested_sha']);
    }

    public function test_signed_wrong_repository_is_still_rejected(): void
    {
        $this->record['repository'] = 'cschibelsky-star/visite-sumare';
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('registry_identity_mismatch');
        $this->measure();
    }

    public function test_shared_network_or_nested_data_mount_blocks_isolation(): void
    {
        $this->record['hml']['networks'] = ['prod'];
        $this->assertFalse($this->measure()['isolated_data']);
        $this->record['hml']['networks'] = ['hml'];
        $this->record['hml']['data_sources'] = ['/prod/data/subdirectory'];
        $this->assertFalse($this->measure()['isolated_data']);
    }

    public function test_successful_workflow_with_skipped_test_cannot_approve(): void
    {
        $this->skipStep = true;
        $this->assertFalse($this->measure()['tests_passed']);
    }

    public function test_dirty_checkout_or_mismatched_image_cannot_be_tested_sha(): void
    {
        $this->record['hml']['checkout_clean'] = false;
        $this->assertSame('', $this->measure()['tested_sha']);
        $this->record['hml']['checkout_clean'] = true;
        $this->record['hml']['image_revision'] = str_repeat('b', 40);
        $this->assertSame('', $this->measure()['tested_sha']);
    }
}
