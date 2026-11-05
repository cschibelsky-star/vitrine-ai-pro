<?php

namespace Tests\Feature;

use App\Assessor\CabinetWorkflow;
use App\Assessor\CabinetOperations;
use App\Assessor\CabinetTasks;
use App\Assessor\CabinetEntitlements;
use DomainException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class AssessorCabinetIsolationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::connection('sqlite')->create('companies', fn (Blueprint $t) => $t->id());
        Schema::connection('sqlite')->create('users', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('company_id');
        });
        $migration = require base_path('database/migrations/2026_10_24_090500_create_assessor_cabinet_workflow_tables.php');
        $migration->up();
        $historyMigration = require base_path('database/migrations/2026_10_24_120000_create_assessor_citizens_appointments_and_history.php');
        $historyMigration->up();
        $tasksMigration = require base_path('database/migrations/2026_10_24_130000_create_assessor_tasks.php');
        $tasksMigration->up();
        DB::table('companies')->insert([['id' => 1], ['id' => 2]]);
        DB::table('users')->insert([
            ['id' => 11, 'company_id' => 1],
            ['id' => 12, 'company_id' => 1],
            ['id' => 21, 'company_id' => 2],
        ]);
    }

    public function test_demand_lifecycle_and_tenant_isolation(): void
    {
        $workflow = app(CabinetWorkflow::class);
        $cabinet = $workflow->createCabinet(1, 11, 'Gabinete', 'vereador');
        $workflow->addAssessor($cabinet, 11, 12);
        $demand = $workflow->createDemand($cabinet, 12, 'Iluminacao', 'Rua escura');
        $workflow->assignDemand($cabinet, 11, $demand, 12);
        $workflow->changeStatus($cabinet, 12, $demand, 'resolved');
        $this->assertSame('resolved', $workflow->listDemands($cabinet, 11)[0]->status);
        $this->expectException(DomainException::class);
        $workflow->listDemands($cabinet, 21);
    }

    public function test_rejects_cross_company_member(): void
    {
        $workflow = app(CabinetWorkflow::class);
        $cabinet = $workflow->createCabinet(1, 11, 'Gabinete', 'vereador');
        $this->expectException(DomainException::class);
        $workflow->addAssessor($cabinet, 11, 21);
    }

    public function test_rejects_cross_company_owner(): void
    {
        $this->expectException(DomainException::class);
        app(CabinetWorkflow::class)->createCabinet(1, 21, 'Gabinete', 'vereador');
    }
    public function test_demand_history_records_only_real_transitions(): void
    {
        $workflow = app(CabinetWorkflow::class);
        $cabinet = $workflow->createCabinet(1, 11, 'Gabinete', 'vereador');
        $workflow->addAssessor($cabinet, 11, 12);
        $demand = $workflow->createDemand($cabinet, 11, 'Saude', 'Atendimento');
        $workflow->assignDemand($cabinet, 11, $demand, 12);
        $workflow->assignDemand($cabinet, 11, $demand, 12);
        $workflow->changeStatus($cabinet, 12, $demand, 'resolved');
        $workflow->changeStatus($cabinet, 12, $demand, 'resolved');

        $events = $workflow->history($cabinet, 11, $demand);
        $this->assertSame(['created', 'assigned', 'status_changed'],
            array_map(fn ($event) => $event->event, $events));
        $this->assertSame('resolved', DB::table('assessor_demands')->where('id', $demand)->value('status'));
    }

    public function test_history_rejects_member_from_another_cabinet(): void
    {
        $workflow = app(CabinetWorkflow::class);
        $cabinet = $workflow->createCabinet(1, 11, 'Gabinete', 'vereador');
        $demand = $workflow->createDemand($cabinet, 11, 'Transporte', 'Solicitacao');
        $this->expectException(DomainException::class);
        $workflow->history($cabinet, 21, $demand);
    }

    public function test_appointment_rejects_citizen_outside_cabinet(): void
    {
        $workflow = app(CabinetWorkflow::class);
        $cabinet = $workflow->createCabinet(1, 11, 'Gabinete', 'vereador');
        $other = $workflow->createCabinet(2, 21, 'Outro gabinete', 'vereador');
        $operations = app(CabinetOperations::class);
        $foreignCitizen = $operations->addCitizen($other, 21, ['name' => 'Cidadao externo']);
        $this->expectException(DomainException::class);
        $operations->schedule($cabinet, 11, [
            'title' => 'Atendimento', 'citizen_id' => $foreignCitizen,
            'starts_at' => '2026-11-06 09:00:00', 'ends_at' => '2026-11-06 10:00:00',
        ]);
    }

    public function test_task_rejects_demand_from_another_cabinet(): void
    {
        $workflow = app(CabinetWorkflow::class);
        $cabinet = $workflow->createCabinet(1, 11, 'Gabinete', 'vereador');
        $other = $workflow->createCabinet(2, 21, 'Outro gabinete', 'vereador');
        $foreignDemand = $workflow->createDemand($other, 21, 'Solicitacao', 'Conteudo');
        $this->expectException(DomainException::class);
        app(CabinetTasks::class)->create($cabinet, 11, [
            'title' => 'Tarefa', 'demand_id' => $foreignDemand,
        ]);
    }

    public function test_assessor_cannot_change_unassigned_task_of_another_member(): void
    {
        $workflow = app(CabinetWorkflow::class);
        $cabinet = $workflow->createCabinet(1, 11, 'Gabinete', 'vereador');
        $workflow->addAssessor($cabinet, 11, 12);
        $tasks = app(CabinetTasks::class);
        $task = $tasks->create($cabinet, 11, ['title' => 'Tarefa interna']);
        $this->expectException(DomainException::class);
        $tasks->updateStatus($cabinet, 12, $task, 'completed');
    }

    public function test_trial_status_rejects_member_after_company_change(): void
    {
        $cabinet = app(CabinetWorkflow::class)->createCabinet(1, 11, 'Gabinete', 'vereador');
        DB::table('users')->where('id', 11)->update(['company_id' => 2]);
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('cabinet_access_denied');
        app(CabinetEntitlements::class)->status($cabinet, 11);
    }

    public function test_trial_status_rejects_invalid_member_role(): void
    {
        $cabinet = app(CabinetWorkflow::class)->createCabinet(1, 11, 'Gabinete', 'vereador');
        DB::table('assessor_cabinet_members')->where('cabinet_id', $cabinet)
            ->where('user_id', 11)->update(['role' => 'viewer']);
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('cabinet_access_denied');
        app(CabinetEntitlements::class)->status($cabinet, 11);
    }

}
