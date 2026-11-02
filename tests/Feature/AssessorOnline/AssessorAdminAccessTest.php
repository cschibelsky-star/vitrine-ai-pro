<?php
declare(strict_types=1);

namespace Tests\Feature\AssessorOnline;

use App\Models\User;
use Filament\Panel;
use Illuminate\Support\Facades\DB;
use Tests\Feature\AssessorOnline\Concerns\UsesIsolatedAssessorDatabase;
use Tests\TestCase;

final class AssessorAdminAccessTest extends TestCase
{
    use UsesIsolatedAssessorDatabase;

    private function addMembership(User $user): void
    {
        $tenantId = DB::table('assessor_tenants')->insertGetId([
            'name' => 'Gabinete Segurança', 'slug' => 'gabinete-seguranca',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('assessor_memberships')->insert([
            'tenant_id' => $tenantId, 'user_id' => $user->id,
            'role' => 'assessor', 'active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_invited_assessor_role_never_grants_core_admin(): void
    {
        $user = User::factory()->create(['role' => 'assessor', 'is_active' => true]);
        $this->addMembership($user);
        $this->assertFalse($user->fresh()->canAccessPanel(Panel::make()->id('admin')));
    }

    public function test_disabled_invitation_cannot_access_core_panel(): void
    {
        $user = User::factory()->create(['role' => 'assessor', 'is_active' => false]);
        $this->addMembership($user);
        $this->assertFalse($user->fresh()->canAccessPanel(Panel::make()->id('admin')));
    }

    public function test_legacy_cabinet_member_cannot_access_core_panel(): void
    {
        $user = User::factory()->create();
        $this->addMembership($user);
        $this->assertFalse($user->canAccessPanel(Panel::make()->id('admin')));
    }

    public function test_explicit_core_admin_can_access_panel(): void
    {
        $user = User::factory()->create();
        $user->role = 'admin';
        $this->assertTrue($user->canAccessPanel(Panel::make()->id('admin')));
    }

    public function test_explicit_assessor_role_cannot_access_core_panel(): void
    {
        $user = User::factory()->create();
        $user->role = 'assessor';
        $this->addMembership($user);
        $this->assertFalse($user->canAccessPanel(Panel::make()->id('admin')));
    }

    public function test_inactive_user_cannot_access_core_panel(): void
    {
        $user = User::factory()->create();
        $user->role = 'admin';
        $user->is_active = false;
        $this->assertFalse($user->canAccessPanel(Panel::make()->id('admin')));
    }

    public function test_legacy_user_without_cabinet_keeps_existing_access(): void
    {
        $user = User::factory()->create();
        $this->assertTrue($user->canAccessPanel(Panel::make()->id('admin')));
    }
}
