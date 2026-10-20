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
        Schema::create('assessor_citizens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
        });
        Schema::create('assessor_cases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('citizen_id');
            $table->uuid('protocol')->unique();
            $table->string('subject');
            $table->text('description');
            $table->text('issue_location')->nullable();
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('assessor_case_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('case_id');
            $table->string('event_type');
            $table->string('to_status');
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('assessor_case_events');
        Schema::dropIfExists('assessor_cases');
        Schema::dropIfExists('assessor_citizens');
        parent::tearDown();
    }

    public function test_registered_case_has_protocol_and_audit_event(): void
    {
        $id = DB::table('assessor_citizens')->insertGetId(['tenant_id' => 10, 'name' => 'Pessoa fictícia']);
        $registered = app(DemandIntakeService::class)->register(10, $id, 'Iluminação', 'Lâmpada apagada');
        $this->assertSame('received', $registered['status']);
        $this->assertNotEmpty($registered['protocol']);
        $this->assertSame(1, DB::table('assessor_cases')->where('tenant_id', 10)->count());
        $this->assertSame(1, DB::table('assessor_case_events')->where('tenant_id', 10)->count());
    }

    public function test_other_tenant_cannot_register_case_for_citizen(): void
    {
        $id = DB::table('assessor_citizens')->insertGetId(['tenant_id' => 10, 'name' => 'Pessoa fictícia']);
        try {
            app(DemandIntakeService::class)->register(11, $id, 'Solicitação', 'Tentativa entre gabinetes');
            $this->fail('Expected validation failure');
        } catch (ValidationException $exception) {
            $this->assertSame(0, DB::table('assessor_cases')->count());
            $this->assertSame(0, DB::table('assessor_case_events')->count());
        }
    }
}
