<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\AssessorOnline\DemandIntakeService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AssessorDemandIsolationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (DB::getDriverName() !== 'sqlite') {
            $this->markTestSkipped('Isolated SQLite test database required.');
        }
        Schema::create('users', function (Blueprint $table) {
            $table->id();
        });
        $migration = require base_path('database/migrations/2026_10_20_000001_create_assessor_core_tables.php');
        $migration->up();

    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('assessor_case_events');
        Schema::dropIfExists('assessor_cases');
        Schema::dropIfExists('assessor_citizens');
        Schema::dropIfExists('assessor_tenants');
        Schema::dropIfExists('users');
        parent::tearDown();
    }

    public function test_registered_case_has_protocol_and_audit_event(): void
    {
        DB::table('assessor_tenants')->insert(['id' => 10, 'name' => 'Gabinete 10', 'slug' => 'gab-10']);
        $id = DB::table('assessor_citizens')->insertGetId(['tenant_id' => 10, 'name' => 'Pessoa fictícia']);
        $registered = app(DemandIntakeService::class)->register(10, $id, 'Iluminação', 'Lâmpada apagada');
        $this->assertSame('received', $registered['status']);
        $this->assertNotEmpty($registered['protocol']);
        $this->assertSame(1, DB::table('assessor_cases')->where('tenant_id', 10)->count());
        $this->assertSame(1, DB::table('assessor_case_events')->where('tenant_id', 10)->count());
    }

    public function test_other_tenant_cannot_register_case_for_citizen(): void
    {
        DB::table('assessor_tenants')->insert(['id' => 10, 'name' => 'Gabinete 10', 'slug' => 'gab-10']);
        $id = DB::table('assessor_citizens')->insertGetId(['tenant_id' => 10, 'name' => 'Pessoa fictícia']);
        try {
            DB::table('assessor_tenants')->insert(['id' => 11, 'name' => 'Gabinete 11', 'slug' => 'gab-11']);
            app(DemandIntakeService::class)->register(11, $id, 'Solicitação', 'Tentativa entre gabinetes');
            $this->fail('Expected validation failure');
        } catch (ValidationException $exception) {
            $this->assertSame(0, DB::table('assessor_cases')->count());
            $this->assertSame(0, DB::table('assessor_case_events')->count());
        }
    }
}
