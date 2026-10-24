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
    public function test_rejects_cross_company_owner(): void
    {
        $this->expectException(DomainException::class);
        app(CabinetWorkflow::class)->createCabinet(1, 99, 'Teste', 'vereador');
    }

    public function test_rejects_unauthorized_member(): void
    {
        $this->expectException(DomainException::class);
        app(CabinetWorkflow::class)->listDemands(999, 99);
    }
}
