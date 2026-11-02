<?php
declare(strict_types=1);

namespace Tests\Feature\AssessorOnline\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait UsesIsolatedAssessorDatabase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'sqlite') {
            $this->markTestSkipped('Isolated SQLite database required.');
        }

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        (require base_path('database/migrations/2026_11_02_000001_create_assessor_password_reset_tokens.php'))->up();
        (require base_path('database/migrations/2026_10_20_000001_create_assessor_core_tables.php'))->up();
        (require base_path('database/migrations/2026_10_22_000002_create_assessor_memberships_table.php'))->up();
        (require base_path('database/migrations/2026_11_02_000002_add_assessor_invitation_pending.php'))->up();
    }

    protected function tearDown(): void
    {
        foreach (['assessor_password_reset_tokens', 'assessor_case_events', 'assessor_cases', 'assessor_memberships', 'assessor_citizens', 'assessor_tenants', 'users'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }
}
