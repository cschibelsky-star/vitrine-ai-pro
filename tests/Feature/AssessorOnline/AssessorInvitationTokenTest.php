<?php
declare(strict_types=1);

namespace Tests\Feature\AssessorOnline;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\Feature\AssessorOnline\Concerns\UsesIsolatedAssessorDatabase;
use Tests\TestCase;

final class AssessorInvitationTokenTest extends TestCase
{
    use UsesIsolatedAssessorDatabase;

    public function test_assessor_token_is_hashed_and_separate_from_core(): void
    {
        $user = User::factory()->create(['role' => 'assessor']);
        $token = Password::broker('assessor')->createToken($user);
        $stored = DB::table('assessor_password_reset_tokens')->where('email', $user->email)->first();
        $this->assertNotNull($stored);
        $this->assertNotSame($token, $stored->token);
        $this->assertTrue(Hash::check($token, $stored->token));
        $this->assertSame(15, config('auth.passwords.assessor.expire'));
        $this->assertSame(60, config('auth.passwords.users.expire'));
        $this->assertTrue(Password::broker('assessor')->tokenExists($user, $token));
    }

    public function test_expired_token_cannot_be_reused(): void
    {
        $user = User::factory()->create(['role' => 'assessor']);
        $token = Password::broker('assessor')->createToken($user);
        DB::table('assessor_password_reset_tokens')->where('email', $user->email)
            ->update(['created_at' => now()->subMinutes(16)]);
        $this->assertFalse(Password::broker('assessor')->tokenExists($user, $token));
    }

    public function test_revoked_membership_cannot_be_reactivated_by_valid_reset_token(): void
    {
        $user = User::factory()->create(['role' => 'assessor', 'is_active' => false]);
        $tenantId = DB::table('assessor_tenants')->insertGetId([
            'name' => 'Gabinete Revogado', 'slug' => 'gabinete-revogado',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('assessor_memberships')->insert([
            'tenant_id' => $tenantId, 'user_id' => $user->id,
            'role' => 'admin', 'active' => false, 'invitation_pending' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $token = Password::broker('assessor')->createToken($user);
        $this->post('/assessor/redefinir-senha', [
            'email' => $user->email, 'token' => $token,
            'password' => 'SenhaNovaDeTeste123!', 'password_confirmation' => 'SenhaNovaDeTeste123!',
        ])->assertSessionHasErrors('email');
        $this->assertFalse((bool) DB::table('assessor_memberships')->where('user_id', $user->id)->value('active'));
    }

    public function test_revoked_token_cannot_be_replayed(): void
    {
        $user = User::factory()->create(['role' => 'assessor']);
        $token = Password::broker('assessor')->createToken($user);
        Password::broker('assessor')->deleteToken($user);
        $this->assertFalse(Password::broker('assessor')->tokenExists($user, $token));
    }
}
