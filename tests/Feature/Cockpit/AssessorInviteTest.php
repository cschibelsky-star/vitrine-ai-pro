<?php
declare(strict_types=1);

namespace Tests\Feature\Cockpit;

use App\Services\CockpitWebmailService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class AssessorInviteTest extends TestCase
{
    private const ENDPOINT = '/cockpit/internal/assessor-invite';
    private const RECIPIENT = 'pilot@example.test';

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.env' => 'homologation', 'cache.default' => 'array',
            'assessor-invite.secret' => str_repeat('test-', 8),
            'assessor-invite.origin' => 'https://assessor.hml.vitrineiapro.com.br',
            'assessor-invite.recipient' => self::RECIPIENT]);
        Cache::flush();
    }

    private function invoke(string $email, ?int $time = null, bool $valid = true): \Illuminate\Testing\TestResponse
    {
        $ts = $time ?? time();
        $token = str_repeat('a', 64);
        $signature = hash_hmac('sha256', "assessor-invite-v1\n{$ts}\n{$email}\n{$token}", config('assessor-invite.secret'));

        return $this->postJson(self::ENDPOINT, ['email' => $email, 'token' => $token], [
            'X-Assessor-Invite-Timestamp' => (string) $ts,
            'X-Assessor-Invite-Signature' => $valid ? $signature : str_repeat('0', 64),
        ]);
    }

    public function test_only_one_delivery_for_the_same_signed_token(): void
    {
        $this->mock(CockpitWebmailService::class)->shouldReceive('send')->once()->with(
            'vendas', self::RECIPIENT, 'Assessor Online - Convite de primeiro acesso',
            \Mockery::on(fn ($body) => str_contains($body, 'https://assessor.hml.vitrineiapro.com.br/assessor/redefinir-senha/'))
        );
        $this->invoke(self::RECIPIENT)->assertOk()->assertExactJson(['ok' => true]);
        $this->invoke(self::RECIPIENT)->assertStatus(409);
    }

    public function test_unauthorized_requests_do_not_send(): void
    {
        $this->mock(CockpitWebmailService::class)->shouldReceive('send')->never();
        $this->invoke('other@example.test')->assertUnauthorized();
        $this->invoke(self::RECIPIENT, time() - 60)->assertUnauthorized();
        $this->invoke(self::RECIPIENT, valid: false)->assertUnauthorized();
        config(['app.env' => 'production']);
        $this->invoke(self::RECIPIENT)->assertNotFound();
    }
}
