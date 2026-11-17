<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\AssessorOnline\CaseWorkflowService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AssessorCaseWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (DB::getDriverName() !== 'sqlite') {
            $this->markTestSkipped('Isolated SQLite database required.');
        }
        Schema::create('users', function (Blueprint $table) {
            $table->id();
        });
        (require base_path('database/migrations/2026_10_20_000001_create_assessor_core_tables.php'))->up();
        (require base_path('database/migrations/2026_10_22_000002_create_assessor_memberships_table.php'))->up();
        DB::table('users')->insert([['id' => 1], ['id' => 2], ['id' => 3]]);
        DB::table('assessor_tenants')->insert([
            ['id' => 10, 'name' => 'Gabinete A', 'slug' => 'gab-a'],
            ['id' => 20, 'name' => 'Gabinete B', 'slug' => 'gab-b'],
        ]);
        DB::table('assessor_memberships')->insert([
            ['tenant_id' => 10, 'user_id' => 1, 'role' => 'assessor', 'active' => true],
            ['tenant_id' => 20, 'user_id' => 2, 'role' => 'admin', 'active' => true],
            ['tenant_id' => 10, 'user_id' => 3, 'role' => 'viewer', 'active' => true],
        ]);
        DB::table('assessor_citizens')->insert([
            ['id' => 100, 'tenant_id' => 10, 'name' => 'Cidadão A'],
            ['id' => 200, 'tenant_id' => 20, 'name' => 'Cidadão B'],
        ]);
        DB::table('assessor_cases')->insert([
            ['id' => 1000, 'tenant_id' => 10, 'citizen_id' => 100, 'protocol' => '02b3a6cc-977b-433d-9a93-1cab1fa78001', 'subject' => 'Iluminação', 'description' => 'Teste', 'status' => 'received'],
            ['id' => 2000, 'tenant_id' => 20, 'citizen_id' => 200, 'protocol' => '02b3a6cc-977b-433d-9a93-1cab1fa78002', 'subject' => 'Transporte', 'description' => 'Teste', 'status' => 'received'],
        ]);
    }

    protected function tearDown(): void
    {
        foreach (['assessor_case_events', 'assessor_cases', 'assessor_memberships', 'assessor_citizens', 'assessor_tenants', 'users'] as $table) {
            Schema::dropIfExists($table);
        }
        parent::tearDown();
    }

    public function test_authorized_transition_persists_event_and_actor(): void
    {
        $result = app(CaseWorkflowService::class)->transition(10, 1000, 1, 'triaged', 'Revisão inicial');
        $this->assertSame('received', $result['previous_status']);
        $this->assertSame('triaged', $result['status']);
        $this->assertSame('triaged', DB::table('assessor_cases')->where('id', 1000)->value('status'));
        $event = DB::table('assessor_case_events')->where('case_id', 1000)->first();
        $this->assertSame(1, $event->actor_user_id);
        $this->assertSame('received', $event->from_status);
        $this->assertSame('triaged', $event->to_status);
    }

    public function test_other_tenant_actor_cannot_modify_case(): void
    {
        $this->expectRejected(fn () => app(CaseWorkflowService::class)->transition(10, 1000, 2, 'triaged'));
    }

    public function test_cross_tenant_case_identifier_cannot_modify_case(): void
    {
        $this->expectRejected(fn () => app(CaseWorkflowService::class)->transition(10, 2000, 1, 'triaged'));
    }

    public function test_viewer_cannot_modify_case(): void
    {
        $this->expectRejected(fn () => app(CaseWorkflowService::class)->transition(10, 1000, 3, 'triaged'));
    }

    public function test_invalid_jump_is_rejected(): void
    {
        $this->expectRejected(fn () => app(CaseWorkflowService::class)->transition(10, 1000, 1, 'closed', 'Teste'));
    }

    public function test_resolution_requires_note(): void
    {
        app(CaseWorkflowService::class)->transition(10, 1000, 1, 'triaged');
        app(CaseWorkflowService::class)->transition(10, 1000, 1, 'in_progress');
        $this->expectRejected(fn () => app(CaseWorkflowService::class)->transition(10, 1000, 1, 'resolved'));
        $this->assertSame(2, DB::table('assessor_case_events')->where('case_id', 1000)->count());
    }

    private function expectRejected(callable $callback): void
    {
        $before = DB::table('assessor_case_events')->count();
        try {
            $callback();
            $this->fail('A validation rejection was required.');
        } catch (ValidationException $exception) {
            $this->assertSame($before, DB::table('assessor_case_events')->count());
        }
    }
}
