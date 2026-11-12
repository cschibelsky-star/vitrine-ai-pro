<?php
declare(strict_types=1);

namespace Tests\Feature\AssessorOnline;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\AssessorOnline\Concerns\UsesIsolatedAssessorDatabase;
use Tests\TestCase;

final class ProvisionAssessorHmlAdminTest extends TestCase
{
    use UsesIsolatedAssessorDatabase {
        setUp as private setUpDatabase;
        tearDown as private tearDownDatabase;
    }

    private array $previousEnvironment = [];
    private const EMAIL = 'owner@example.test';

    protected function setUp(): void
    {
        $this->setUpDatabase();
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')->nullable();
            $table->boolean('is_active')->default(true);
        });
        // Use a non-default token table to prove cleanup follows the broker.
        Schema::create('assessor_test_tokens', function (Blueprint $table): void {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
        config([
            'app.env' => 'homologation',
            'app.url' => 'https://assessor.hml.vitrineiapro.com.br',
            'mail.default' => 'smtp',
            'mail.from.address' => 'noreply@example.test',
            'mail.mailers.smtp.host' => 'smtp.example.test',
            'auth.passwords.users.table' => 'assessor_test_tokens',
            'auth.passwords.users.expire' => 30,
            'hashing.bcrypt.rounds' => 4,
        ]);
        foreach (['ASSESSOR_HML_PROVISION_ENABLED' => 'true', 'ASSESSOR_HML_ADMIN_EMAIL' => self::EMAIL] as $key => $value) {
            $this->previousEnvironment[$key] = Env::getRepository()->get($key);
            Env::getRepository()->set($key, $value);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->previousEnvironment as $key => $value) {
            $value === null ? Env::getRepository()->clear($key) : Env::getRepository()->set($key, $value);
        }
        Schema::dropIfExists('assessor_test_tokens');
        $this->tearDownDatabase();
    }

    private function invite(?string $email = null): void
    {
        $this->artisan('assessor:provision-hml-admin', ['email' => $email ?? self::EMAIL])
            ->assertExitCode(1);
    }

    private function failDelivery(?callable $beforeFailure = null): void
    {
        Mail::shouldReceive('raw')->once()->andReturnUsing(function () use ($beforeFailure): void {
            if ($beforeFailure !== null) {
                $beforeFailure();
            }
            throw new \RuntimeException('Simulated transport failure');
        });
    }

    public function test_delivery_failure_removes_only_resources_created_by_the_attempt(): void
    {
        $this->failDelivery();
        $this->invite();
        foreach (['users', 'assessor_memberships', 'assessor_test_tokens', 'assessor_tenants'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
    }

    public function test_delivery_failure_preserves_an_existing_cabinet(): void
    {
        DB::table('assessor_tenants')->insert(['name' => 'Existing', 'slug' => 'gabinete-demonstracao']);
        $this->failDelivery();
        $this->invite();
        $this->assertDatabaseCount('assessor_tenants', 1);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('assessor_test_tokens', 0);
    }

    public function test_new_cabinet_is_preserved_when_it_has_acquired_citizen_data(): void
    {
        $this->failDelivery(function (): void {
            DB::table('assessor_citizens')->insert([
                'tenant_id' => DB::table('assessor_tenants')->value('id'),
                'name' => 'Preserved Citizen',
            ]);
        });
        $this->invite();
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('assessor_tenants', 1);
        $this->assertDatabaseCount('assessor_citizens', 1);
    }

    public function test_invitation_uses_configured_expiration_and_broker_hash(): void
    {
        $token = null;
        Mail::shouldReceive('raw')->once()->andReturnUsing(function (string $body) use (&$token): void {
            $this->assertStringContainsString('30 minutos', $body);
            $this->assertStringContainsString('https://', $body);
            $this->assertSame(1, preg_match('~/redefinir-senha/([^?\s]+)~', $body, $matches));
            $token = $matches[1];
        });
        $this->artisan('assessor:provision-hml-admin', ['email' => self::EMAIL])->assertExitCode(0);
        $user = User::where('email', self::EMAIL)->firstOrFail();
        $this->assertSame('assessor', $user->role);
        $this->assertDatabaseHas('assessor_memberships', ['user_id' => $user->id, 'role' => 'admin']);
        $this->assertNotSame($token, DB::table('assessor_test_tokens')->value('token'));
        $this->assertTrue(Password::broker('users')->tokenExists($user, $token));
        $this->travel(31)->minutes();
        $this->assertFalse(Password::broker('users')->tokenExists($user, $token));
        $this->travelBack();
    }

    public function test_duplicate_identity_is_not_deleted_on_failure(): void
    {
        $user = User::factory()->create(['email' => self::EMAIL]);
        Mail::shouldReceive('raw')->never();
        $this->invite();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => self::EMAIL]);
        $this->assertDatabaseCount('assessor_tenants', 0);
    }

    public function test_http_url_is_rejected_without_database_changes(): void
    {
        config(['app.url' => 'http://assessor.hml.vitrineiapro.com.br']);
        Mail::shouldReceive('raw')->never();
        $this->invite();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_production_is_rejected(): void
    {
        config(['app.env' => 'production']);
        Mail::shouldReceive('raw')->never();
        $this->invite();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_disabled_flag_is_rejected(): void
    {
        Env::getRepository()->set('ASSESSOR_HML_PROVISION_ENABLED', 'false');
        Mail::shouldReceive('raw')->never();
        $this->invite();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_unauthorized_recipient_is_rejected(): void
    {
        Mail::shouldReceive('raw')->never();
        $this->invite('other@example.test');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_log_mailer_is_rejected_without_creating_tokens(): void
    {
        config(['mail.default' => 'log']);
        Mail::shouldReceive('raw')->never();
        $this->invite();
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('assessor_test_tokens', 0);
    }

    public function test_invalid_expiration_is_rejected_before_account_creation(): void
    {
        config(['auth.passwords.users.expire' => 0]);
        Mail::shouldReceive('raw')->never();
        $this->invite();
        $this->assertDatabaseCount('users', 0);
    }
}
