<?php

namespace Tests\Feature;

use App\Assessor\CabinetWorkflow;
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
}
