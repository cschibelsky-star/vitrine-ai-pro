<?php
declare(strict_types=1);

namespace Tests\Feature\AssessorOnline;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\Feature\AssessorOnline\Concerns\UsesIsolatedAssessorDatabase;
use Tests\TestCase;

final class AssessorAuthenticationTest extends TestCase
{
    use UsesIsolatedAssessorDatabase;

    private function grantCabinet(User $user, string $slug = 'gabinete-teste'): void
    {
        $tenantId = DB::table('assessor_tenants')->insertGetId([
            'name' => 'Gabinete Teste', 'slug' => $slug,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('assessor_memberships')->insert([
            'tenant_id' => $tenantId, 'user_id' => $user->id,
            'role' => 'assessor', 'active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_guest_sees_dedicated_login_and_is_redirected_to_it(): void
    {
        $this->get('/assessor/login')->assertOk()->assertSee('Assessor Online');
        $this->get('/assessor/painel')->assertRedirect('/assessor/login');
    }

    public function test_member_can_login_and_logout(): void
    {
        $user = User::factory()->create(['password' => 'senha-segura-teste']);
        $this->grantCabinet($user);
        $this->post('/assessor/login', [
            'email' => $user->email, 'password' => 'senha-segura-teste',
        ])->assertRedirect('/assessor/painel');
        $this->assertAuthenticatedAs($user);
        $this->post('/assessor/logout')->assertRedirect('/assessor/login');
        $this->assertGuest();
    }

    public function test_user_without_membership_is_rejected(): void
    {
        $user = User::factory()->create(['password' => 'senha-segura-teste']);
        $this->post('/assessor/login', [
            'email' => $user->email, 'password' => 'senha-segura-teste',
        ])->assertRedirect('/assessor/login')->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_multiple_active_cabinets_cannot_login_without_selection(): void
    {
        $user = User::factory()->create(['password' => 'senha-segura-teste']);
        $this->grantCabinet($user, 'gabinete-um');
        $this->grantCabinet($user, 'gabinete-dois');
        $this->post('/assessor/login', [
            'email' => $user->email, 'password' => 'senha-segura-teste',
        ])->assertRedirect('/assessor/login')->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_invalid_password_is_rejected(): void
    {
        $user = User::factory()->create(['password' => 'senha-segura-teste']);
        $this->grantCabinet($user);
        $this->post('/assessor/login', [
            'email' => $user->email, 'password' => 'senha-errada',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
