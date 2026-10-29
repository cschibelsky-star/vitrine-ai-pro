<?php
declare(strict_types=1);

namespace Tests\Feature\AssessorOnline;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class DemandTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function seedCabinet(int $userId, string $slug): array
    {
        $tenantId = DB::table('assessor_tenants')->insertGetId([
            'name' => 'Gabinete ' . $slug, 'slug' => $slug,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('assessor_memberships')->insert([
            'tenant_id' => $tenantId, 'user_id' => $userId, 'role' => 'assessor',
            'active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $citizenId = DB::table('assessor_citizens')->insertGetId([
            'tenant_id' => $tenantId, 'name' => 'Cidadão de teste',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $caseId = DB::table('assessor_cases')->insertGetId([
            'tenant_id' => $tenantId, 'citizen_id' => $citizenId,
            'protocol' => (string) \Illuminate\Support\Str::uuid(),
            'subject' => 'Demanda ' . $slug, 'description' => 'Teste de isolamento',
            'status' => 'received', 'created_at' => now(), 'updated_at' => now(),
        ]);
        return [$tenantId, $caseId];
    }

    public function test_assessor_cannot_view_or_modify_another_cabinets_case(): void
    {
        $user = User::factory()->create();
        [$ownTenant, $ownCase] = $this->seedCabinet($user->id, 'proprio');
        $otherUser = User::factory()->create();
        [$otherTenant, $otherCase] = $this->seedCabinet($otherUser->id, 'outro');

        $this->actingAs($user)->get('/assessor/demandas')->assertOk()
            ->assertSee('Demanda proprio')->assertDontSee('Demanda outro');
        $this->get('/assessor/demandas/' . $otherCase)->assertNotFound();
        $this->post('/assessor/demandas/' . $otherCase . '/situacao', [
            'status' => 'triaged',
        ])->assertSessionHasErrors('case');

        $this->assertDatabaseHas('assessor_cases', [
            'id' => $otherCase, 'tenant_id' => $otherTenant, 'status' => 'received',
        ]);
        $this->assertDatabaseHas('assessor_cases', [
            'id' => $ownCase, 'tenant_id' => $ownTenant, 'status' => 'received',
        ]);
    }

    public function test_user_without_active_membership_cannot_access_demands(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/assessor/demandas')->assertForbidden();
    }

    public function test_user_with_multiple_cabinets_must_select_an_explicit_context(): void
    {
        $user = User::factory()->create();
        $this->seedCabinet($user->id, 'primeiro');
        $this->seedCabinet($user->id, 'segundo');
        $this->actingAs($user)->get('/assessor/demandas')->assertForbidden();
    }

    public function test_valid_status_change_is_audited(): void
    {
        $user = User::factory()->create();
        [$tenantId, $caseId] = $this->seedCabinet($user->id, 'auditoria');
        $this->actingAs($user)->post('/assessor/demandas/' . $caseId . '/situacao', [
            'status' => 'triaged',
        ])->assertRedirect('/assessor/demandas/' . $caseId);
        $this->assertDatabaseHas('assessor_cases', [
            'id' => $caseId, 'tenant_id' => $tenantId, 'status' => 'triaged',
        ]);
        $this->assertDatabaseHas('assessor_case_events', [
            'tenant_id' => $tenantId, 'case_id' => $caseId,
            'actor_user_id' => $user->id, 'to_status' => 'triaged',
        ]);
    }
}
