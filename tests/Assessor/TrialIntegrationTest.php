<?php
namespace Tests\Assessor;
use App\Assessor\{TrialService,TrialRequest,HmlTrialProvider,TrialPolicy,UnavailableHmlTrialProvider};
use Illuminate\Support\Facades\{Schema,DB};
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;
final class TrialIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['assessor_trial.enabled'=>true,'database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:']);
        DB::purge('sqlite');
        Schema::create('users',function(Blueprint $t){$t->id();});
        Schema::create('companies',function(Blueprint $t){$t->id();});
        (require __DIR__.'/../../database/migrations/2026_10_13_230000_create_assessor_trial_requests.php')->up();
        DB::table('users')->insert(['id'=>1]); DB::table('companies')->insert(['id'=>7]);
    }
    public function test_unconfigured_provider_never_starts_clock(): void
    {
        $s=new TrialService(new UnavailableHmlTrialProvider);
        $t=$s->request(1,7,'Gabinete sintético','vereador');
        $t=$s->provision($t->id);
        $this->assertSame('blocked',$t->status);$this->assertNull($t->starts_at);$this->assertNull($t->ends_at);
        $t=$s->stop($t->id,'cancelled');$this->assertSame('cancelled_pending',$t->status);
        $this->assertFalse(TrialPolicy::accessible($t->status,$t->starts_at,$t->ends_at,new \DateTimeImmutable));
    }
    public function test_activation_retry_cancel_and_no_billing(): void
    {
        $provider=new class implements HmlTrialProvider {
            public int $calls=0;
            public function provision(string $id,int $company,string $host):array {
                $this->calls++;
                return ['tenant_id'=>$id,'company_id'=>$company,'hostname'=>$host,'environment'=>'hml','route_id'=>'official-route',
                    'dns_valid'=>true,'tls_valid'=>true,'healthy'=>true,'isolated'=>true,'cockpit_registered'=>true,'access_ready'=>true];
            }
            public function revoke(string $id):void {}
        };
        $s=new TrialService($provider);$t=$s->request(1,7,'Gabinete sintético','deputado');
        $t=$s->provision($t->id);$start=$t->starts_at;
        $this->assertSame('active',$t->status);
        $this->assertSame(30*86400,$t->ends_at->getTimestamp()-$start->getTimestamp());
        $s->provision($t->id);$this->assertSame(1,$provider->calls);
        $t=$s->stop($t->id,'cancelled');$this->assertSame('cancelled',$t->status);
        $this->assertSame($start->getTimestamp(),$t->starts_at->getTimestamp());
        $this->assertFalse(Schema::hasTable('payments'));
        $this->assertSame(1,TrialRequest::count());
        $this->expectException(\DomainException::class);$s->request(1,7,'Outro nome','vereador');
    }
    public function test_gate_disabled_blocks_even_request():void
    {
        config(['assessor_trial.enabled'=>false]);
        $this->expectException(\DomainException::class);
        (new TrialService(new UnavailableHmlTrialProvider))->request(1,7,'Sintético','vereador');
    }
    public function test_request_ignores_posted_company_and_requires_consent():void
    {
        $u=new \App\Models\User;
        $u->forceFill(['id'=>1,'company_id'=>7,'is_active'=>true,'email_verified_at'=>now(),'role'=>'client']);
        $this->actingAs($u)->post('/assessor/avaliacao',['cabinet_name'=>'Sintético','mandate_type'=>'vereador','terms_version'=>config('assessor_trial.terms_version')])->assertSessionHasErrors('consent');
        $this->actingAs($u)->post('/assessor/avaliacao',['company_id'=>999,'cabinet_name'=>'Sintético','mandate_type'=>'vereador','consent'=>1,'terms_version'=>config('assessor_trial.terms_version')])->assertRedirect('/assessor/avaliacao');
        $this->assertSame(7,(int)TrialRequest::first()->company_id);
    }
    public function test_another_company_cannot_cancel_or_view_request():void
    {
        $s=new TrialService(new UnavailableHmlTrialProvider);$t=$s->request(1,7,'Sintético','vereador');
        $u=new \App\Models\User;
        $u->forceFill(['id'=>2,'company_id'=>8,'is_active'=>true,'email_verified_at'=>now(),'role'=>'client']);
        $this->actingAs($u)->post('/assessor/avaliacao/'.$t->id.'/cancelar')->assertNotFound();
        $this->actingAs($u)->get('/assessor/avaliacao')->assertOk()->assertDontSee('Sintético');
        $this->assertSame('requested',$t->fresh()->status);
    }
    public function test_non_client_user_is_denied_trial_enrollment():void
    {
        $u=new \App\Models\User;
        $u->forceFill(['id'=>1,'company_id'=>7,'is_active'=>true,'email_verified_at'=>now(),'role'=>'admin']);
        $this->actingAs($u)->post('/assessor/avaliacao',[
            'cabinet_name'=>'Gabinete sintético',
            'mandate_type'=>'vereador',
            'terms_version'=>config('assessor_trial.terms_version'),
            'consent'=>1,
        ])->assertForbidden();
        $this->assertSame(0,TrialRequest::count());
    }
    public function test_routes_hidden_when_disabled():void
    {
        config(['assessor_trial.enabled'=>false]);
        $u=new \App\Models\User;$u->forceFill(['id'=>1,'company_id'=>7,'is_active'=>true,'email_verified_at'=>now()]);
        $this->actingAs($u)->get('/assessor/avaliacao')->assertNotFound();
    }
    public function test_anonymous_requests_are_hidden_when_disabled_and_rejected_when_enabled():void
    {
        config(['assessor_trial.enabled'=>false]);
        $this->get('/assessor/avaliacao')->assertNotFound();
        config(['assessor_trial.enabled'=>true]);
        $this->get('/assessor/avaliacao')->assertUnauthorized();
    }

    public function test_expired_trial_reconciles_pending_revocation_without_reopening_access(): void
    {
        $provider = new class implements HmlTrialProvider {
            public int $revokeCalls = 0;
            public function provision(string $id, int $company, string $host): array
            {
                return ['tenant_id'=>$id,'company_id'=>$company,'hostname'=>$host,
                    'environment'=>'hml','route_id'=>'route-hml-synthetic',
                    'dns_valid'=>true,'tls_valid'=>true,'healthy'=>true,
                    'isolated'=>true,'cockpit_registered'=>true,'access_ready'=>true];
            }
            public function revoke(string $tenantId): void
            {
                $this->revokeCalls++;
                if ($this->revokeCalls === 1) {
                    throw new \RuntimeException('synthetic revocation failure');
                }
            }
        };
        $this->app->instance(HmlTrialProvider::class, $provider);
        $service = $this->app->make(TrialService::class);
        $trial = $service->request(1, 7, 'Gabinete de teste', 'vereador');
        $trial = $service->provision($trial->id);
        $this->assertSame('active', $trial->status);
        $trial->forceFill(['ends_at' => now('UTC')->subMinute()])->save();

        // Expiration denies access before the provider finishes revocation.
        $this->assertFalse(TrialPolicy::accessible('active', $trial->starts_at, $trial->ends_at, new \DateTimeImmutable('now')));
        $this->artisan('assessor:trial expire')->assertExitCode(1);
        $this->assertSame('expired_pending', $trial->fresh()->status);
        $this->assertFalse(TrialPolicy::accessible($trial->fresh()->status, $trial->fresh()->starts_at, $trial->ends_at, new \DateTimeImmutable('now')));

        $this->artisan('assessor:trial expire')->assertExitCode(0);
        $this->assertSame('expired', $trial->fresh()->status);
        $this->artisan('assessor:trial expire')->assertExitCode(0);
        $this->assertSame(2, $provider->revokeCalls);
        $this->assertSame(1, TrialRequest::count());
        $this->assertFalse(Schema::hasTable('payments'));
    }
}
