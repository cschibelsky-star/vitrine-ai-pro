<?php
declare(strict_types=1);

namespace Tests\Feature\AssessorOnline;

use Tests\TestCase;

final class DemandManagementTest extends TestCase
{
    public function test_guest_cannot_access_demand_listing(): void
    {
        $response = $this->get('/assessor/demandas');
        $response->assertRedirect('/admin/login');
    }

    public function test_guest_cannot_read_demand_details(): void
    {
        $response = $this->get('/assessor/demandas/1');
        $response->assertRedirect('/admin/login');
    }

    public function test_guest_cannot_change_demand_status(): void
    {
        $response = $this->post('/assessor/demandas/1/situacao', [
            'status' => 'closed',
            'note' => 'Tentativa sem autenticação',
        ]);
        $response->assertRedirect('/admin/login');
    }
}
