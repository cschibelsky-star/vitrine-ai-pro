<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Deploy\HomologationGate;
use App\Services\Deploy\PublicationControl;
use App\Services\Deploy\ReleaseLedger;
use RuntimeException;
use Tests\TestCase;

final class PublicationControlTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'array', 'session.driver' => 'array']);
        $this->directory = sys_get_temp_dir().'/publication-http-'.bin2hex(random_bytes(8));
        $control = new PublicationControl(new HomologationGate(), new ReleaseLedger($this->directory),
            fn () => throw new RuntimeException('No trusted operational evidence'), fn () => true);
        $this->app->instance(PublicationControl::class, $control);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory.'/*') ?: [] as $path) { unlink($path); }
        if (is_dir($this->directory)) { rmdir($this->directory); }
        parent::tearDown();
    }

    public function test_machine_endpoint_rejects_missing_credentials(): void
    {
        config(['publication.executor_token' => str_repeat('t', 32)]);
        $this->postJson('/api/publication/consume', [])->assertUnauthorized();
    }

    public function test_forged_checks_cannot_authorize_publication(): void
    {
        config(['publication.executor_token' => str_repeat('t', 32)]);
        $this->postJson('/api/publication/consume', [
            'project' => 'conheca-sumare', 'sha' => str_repeat('a', 40), 'executor' => 'conheca-sumare-vps',
            'ci_green' => true, 'isolated_data' => true, 'approved_by_authorized_user' => true,
        ], ['Authorization' => 'Bearer '.str_repeat('t', 32)])->assertStatus(409)->assertJson(['allowed' => false]);
    }

    public function test_client_cannot_approve(): void
    {
        $client = new User(['role' => 'client', 'is_active' => true]);
        $client->id = 42;
        $this->actingAs($client)->postJson('/admin/publication/approve', [])->assertForbidden();
    }

    public function test_admin_cannot_approve_without_evidence(): void
    {
        $admin = new User(['role' => 'admin', 'is_active' => true]);
        $admin->id = 42;
        $this->actingAs($admin)->postJson('/admin/publication/approve', [
            'project' => 'conheca-sumare', 'sha' => str_repeat('a', 40), 'tests_passed' => true,
        ])->assertStatus(409)->assertJson(['allowed' => false]);
    }

    public function test_forged_webhook_is_rejected(): void
    {
        config(['publication.webhook_secret' => str_repeat('w', 32)]);
        $this->postJson('/api/publication/github-push', [], ['X-Hub-Signature-256' => 'sha256=forged', 'X-GitHub-Event' => 'push'])->assertUnauthorized();
    }
}
