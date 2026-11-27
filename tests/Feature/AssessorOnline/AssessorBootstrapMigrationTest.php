<?php
declare(strict_types=1);

namespace Tests\Feature\AssessorOnline;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class AssessorBootstrapMigrationTest extends TestCase
{
    private const MIGRATIONS = [
        'database/migrations/2024_01_01_000001_create_users_table.php',
        'database/migrations/2026_10_20_000001_create_assessor_core_tables.php',
        'database/migrations/2026_10_22_000002_create_assessor_memberships_table.php',
        'database/migrations/2026_11_01_000003_add_assessor_auth_columns_to_users.php',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        if (DB::getDriverName() !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Requires isolated in-memory SQLite.');
        }
        config(['hashing.bcrypt.rounds' => 4]);
    }

    private function migrateDockerSequence(): void
    {
        $dockerfile = file_get_contents(base_path('docker/assessor-laravel/Dockerfile'));
        $this->assertNotFalse($dockerfile);
        preg_match('/^CMD (.+)$/m', $dockerfile, $cmdLine);
        $this->assertArrayHasKey(1, $cmdLine);
        $cmd = json_decode($cmdLine[1], true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(['sh', '-lc'], array_slice($cmd, 0, 2));
        $this->assertCount(3, $cmd);
        preg_match_all('~php artisan migrate --path=([^\s]+) --force~', $cmd[2], $matches);
        // Only execute the four explicitly reviewed migrations against SQLite.
        $this->assertSame(self::MIGRATIONS, $matches[1]);
        $this->assertStringContainsString(end($matches[0]).' && php artisan serve', $cmd[2]);
        foreach ($matches[1] as $path) {
            $this->artisan('migrate', ['--path' => $path, '--force' => true])->assertExitCode(0);
        }
    }

    private function createCabinetAdmin(): User
    {
        $user = User::create([
            'name' => 'Bootstrap Test', 'email' => 'bootstrap@example.test',
            'password' => 'isolated-test-password-1234',
            'role' => 'assessor', 'is_active' => true,
        ]);
        $tenantId = DB::table('assessor_tenants')->insertGetId([
            'name' => 'Bootstrap Cabinet', 'slug' => 'bootstrap-test',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('assessor_memberships')->insert([
            'tenant_id' => $tenantId, 'user_id' => $user->id,
            'role' => 'admin', 'active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return $user;
    }

    public function test_fresh_bootstrap_supports_the_real_account_fields(): void
    {
        $this->migrateDockerSequence();
        $this->assertTrue(Schema::hasColumns('users', ['role', 'is_active']));
        $this->assertTrue(Schema::hasTable('password_reset_tokens'));
        $user = $this->createCabinetAdmin()->fresh();
        $this->assertSame('assessor', $user->role);
        $this->assertTrue($user->is_active);
        $this->assertDatabaseHas('assessor_memberships', ['user_id' => $user->id, 'role' => 'admin']);
    }

    public function test_restart_migrations_preserve_account_and_membership(): void
    {
        $this->migrateDockerSequence();
        $user = $this->createCabinetAdmin();
        $passwordHash = $user->password;
        $this->migrateDockerSequence();
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('assessor_memberships', 1);
        $this->assertSame($passwordHash, $user->fresh()->password);
        $this->assertSame(4, DB::table('migrations')->count());
    }

    public function test_auth_migration_preserves_existing_explicit_roles_and_disabled_accounts(): void
    {
        $this->migrateDockerSequence();
        $user = $this->createCabinetAdmin();
        $user->forceFill(['role' => 'admin', 'is_active' => false])->save();
        // Direct reapplication validates hasColumn guards, beyond migrate's history checks.
        (require base_path(self::MIGRATIONS[3]))->up();
        $this->assertSame('admin', $user->fresh()->role);
        $this->assertFalse($user->fresh()->is_active);
    }
}
