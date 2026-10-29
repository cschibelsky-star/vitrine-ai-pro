<?php
declare(strict_types=1);

namespace Tests\Feature\AssessorOnline;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class CitizenManagementTest extends TestCase
{
    use RefreshDatabase;

    private function cabinet(User $user, string $slug): int
    {
        $tenantId = DB::table('assessor_tenants')->insertGetId([
            'name' => 'Gabinete ' . $slug, 'slug' => $slug,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('assessor_memberships')->insert([
            'tenant_id' => $tenantId, 'user_id' => $user->id,
            'role' => 'assessor', 'active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return $tenantId;
    }

    public function test_citizen_registration_and_demand_intake_create_a_protocol_and_event(): void
    {
        $user = User::factory()->create();
        $tenantId = $this->cabinet($user, 'cadastro');
        $this->actingAs($user)->post('/assessor/cidadaos', [
            'name' => 'Maria Silva', 'email' => 'maria@example.org', 'phone' => '11999990000',
        ])->assertRedirect();
        $citizenId = DB::table('assessor_citizens')->where('tenant_id', $tenantId)->value('id');
        $this->assertNotNull($citizenId);
        $this->assertDatabaseHas('assessor_citizens', [
            'id' => $citizenId, 'tenant_id' => $tenantId, 'name' => 'Maria Silva',
        ]);
        $this->post('/assessor/cidadaos/' . $citizenId . '/demandas', [
            'subject' => 'Iluminação pública', 'description' => 'Poste sem iluminação',
        ])->assertRedirect();
        $case = DB::table('assessor_cases')->where('tenant_id', $tenantId)
            ->where('citizen_id', $citizenId)->first();
        $this->assertNotNull($case);
        $this->assertNotEmpty($case->protocol);
        $this->assertSame('received', $case->status);
        $this->assertDatabaseHas('assessor_case_events', [
            'tenant_id' => $tenantId, 'case_id' => $case->id,
            'event_type' => 'received', 'to_status' => 'received',
        ]);
    }

    public function test_citizen_cannot_be_read_or_used_across_cabinets(): void
    {
        $user = User::factory()->create();
        $this->cabinet($user, 'origem');
        $other = User::factory()->create();
        $otherTenantId = $this->cabinet($other, 'destino');
        $foreignCitizenId = DB::table('assessor_citizens')->insertGetId([
            'tenant_id' => $otherTenantId, 'name' => 'Pessoa restrita',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->actingAs($user)->get('/assessor/cidadaos/' . $foreignCitizenId)->assertNotFound();
        $this->post('/assessor/cidadaos/' . $foreignCitizenId . '/demandas', [
            'subject' => 'Tentativa externa', 'description' => 'Não deve ser aceita',
        ])->assertSessionHasErrors('citizen');
        $this->assertDatabaseCount('assessor_cases', 0);
    }

    public function test_citizen_registration_requires_valid_name(): void
    {
        $user = User::factory()->create();
        $this->cabinet($user, 'validacao');
        $this->actingAs($user)->post('/assessor/cidadaos', [
            'name' => '', 'email' => 'invalido',
        ])->assertSessionHasErrors(['name', 'email']);
        $this->assertDatabaseCount('assessor_citizens', 0);
    }

    public function test_dashboard_only_counts_current_cabinet(): void
    {
        $user = User::factory()->create();
        $tenantId = $this->cabinet($user, 'painel');
        $other = User::factory()->create();
        $otherTenant = $this->cabinet($other, 'painel-outro');
        foreach ([$tenantId, $otherTenant] as $id) {
            DB::table('assessor_citizens')->insert([
                'tenant_id' => $id, 'name' => 'Pessoa ' . $id,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        $this->actingAs($user)->get('/assessor/painel')
            ->assertOk()->assertViewHas('citizenCount', 1)->assertViewHas('caseCount', 0);
    }
}
