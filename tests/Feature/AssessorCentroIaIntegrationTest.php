<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class AssessorCentroIaIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'assessor_ai.enabled' => true, 'assessor_ai.broker_url' => 'https://core.test/api/internal/centro-ia/execute',
            'assessor_ai.token' => 'fixture-token']);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $t) { $t->id(); $t->integer('company_id'); });
        Schema::create('assessor_cabinets', function (Blueprint $t) { $t->id(); $t->integer('company_id'); $t->string('name'); });
        Schema::create('assessor_cabinet_members', function (Blueprint $t) {
            $t->integer('cabinet_id'); $t->integer('user_id'); $t->string('role');
        });
        Schema::create('assessor_demands', function (Blueprint $t) {
            $t->id(); $t->integer('cabinet_id'); $t->string('status'); $t->string('description')->nullable();
        });
        Schema::create('assessor_tasks', function (Blueprint $t) {
            $t->id(); $t->integer('cabinet_id'); $t->string('status'); $t->date('due_date')->nullable();
        });
        DB::table('users')->insert([['id'=>11,'company_id'=>1],['id'=>12,'company_id'=>1],['id'=>21,'company_id'=>2]]);
        DB::table('assessor_cabinets')->insert([
            ['id'=>1,'company_id'=>1,'name'=>'Gabinete privado'],['id'=>2,'company_id'=>2,'name'=>'Outro']]);
        DB::table('assessor_cabinet_members')->insert([
            ['cabinet_id'=>1,'user_id'=>11,'role'=>'admin'],
            ['cabinet_id'=>1,'user_id'=>12,'role'=>'assessor'],
            ['cabinet_id'=>2,'user_id'=>21,'role'=>'admin']]);
        DB::table('assessor_demands')->insert([
            ['cabinet_id'=>1,'status'=>'open','description'=>'Cidadão confidencial telefone 99999'],
            ['cabinet_id'=>2,'status'=>'open','description'=>'Outro segredo']]);
        Http::preventStrayRequests();
    }

    private function actor(int $id = 11): void
    {
        $user = new User;
        $user->forceFill(['id'=>$id, 'company_id'=>$id === 21 ? 2 : 1]);
        $this->actingAs($user);
    }

    private function broker(array $overrides = [], int $status = 200): void
    {
        Http::fake(['core.test/*' => Http::response(array_merge([
            'ok'=>true, 'project_id'=>'assessor-online', 'capability'=>'assistant_conversation', 'execution_id'=>123,
            'output_text'=>json_encode(['recommendations'=>[
                ['priority'=>'medium','title'=>'Triar demandas','reason'=>'Uma demanda aberta.']]])
        ], $overrides), $status)]);
    }

    public function test_authenticated_post_uses_broker_and_only_cabinet_aggregates(): void
    {
        $this->actor(); $this->broker();
        $this->postJson('/assessor/gabinetes/1/inteligencia', ['cabinet_id'=>2, 'model'=>'ignored'])
            ->assertOk()->assertJsonPath('data.source','centro_ia')->assertJsonPath('data.ai_connected',true)
            ->assertJsonPath('data.execution_id',123)->assertJsonPath('data.context.metrics.open_demands',1);
        Http::assertSent(function ($request) {
            $metrics = json_decode($request['input']['user'], true);
            return $request->hasHeader('Authorization', 'Bearer fixture-token')
                && $request->hasHeader('X-Vitrine-Project','assessor-online')
                && $metrics === ['open_demands'=>1,'in_progress_demands'=>0,'resolved_demands'=>0,'pending_tasks'=>0,'overdue_tasks'=>0]
                && !str_contains($request->body(), 'confidencial')
                && !str_contains($request->body(), 'Gabinete privado')
                && !isset($request['model']);
        });
        Http::assertSentCount(1);
    }

    public function test_get_never_calls_paid_generation(): void
    {
        $this->actor();
        $this->getJson('/assessor/gabinetes/1/inteligencia')->assertOk()->assertJsonPath('data.source','local_rules');
        Http::assertNothingSent();
    }

    public function test_unauthenticated_post_is_denied(): void
    {
        $this->postJson('/assessor/gabinetes/1/inteligencia')->assertUnauthorized();
        Http::assertNothingSent();
    }

    public function test_cross_cabinet_access_is_denied_before_transport(): void
    {
        $this->actor(21);
        $this->postJson('/assessor/gabinetes/1/inteligencia')->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_stale_membership_after_company_change_is_denied(): void
    {
        $this->actor();
        DB::table('users')->where('id',11)->update(['company_id'=>2]);
        $this->postJson('/assessor/gabinetes/1/inteligencia')->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_unknown_role_and_revoked_membership_are_denied(): void
    {
        $this->actor();
        DB::table('assessor_cabinet_members')->where('user_id',11)->update(['role'=>'viewer']);
        $this->postJson('/assessor/gabinetes/1/inteligencia')->assertForbidden();
        DB::table('assessor_cabinet_members')->where('user_id',11)->delete();
        $this->postJson('/assessor/gabinetes/1/inteligencia')->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_assessor_role_can_generate_aggregate_recommendations(): void
    {
        $this->actor(12); $this->broker();
        $this->postJson('/assessor/gabinetes/1/inteligencia')->assertOk()->assertJsonPath('data.source','centro_ia');
    }

    public function test_disabled_integration_preserves_local_fallback(): void
    {
        $this->actor(); config(['assessor_ai.enabled'=>false]);
        $this->postJson('/assessor/gabinetes/1/inteligencia')->assertOk()
            ->assertJsonPath('data.source','local_rules')->assertJsonPath('data.fallback_reason','disabled');
        Http::assertNothingSent();
    }

    public function test_http_error_preserves_local_rules_without_exposing_body(): void
    {
        $this->actor(); $this->broker(['message'=>'sensitive provider details'], 429);
        $response=$this->postJson('/assessor/gabinetes/1/inteligencia');
        $response->assertOk()->assertJsonPath('data.source','local_rules')->assertJsonPath('data.ai_connected',false)
            ->assertJsonPath('data.fallback_reason','broker_rejected');
        $this->assertStringNotContainsString('sensitive provider details', $response->getContent());
    }

    public function test_invalid_output_is_not_presented_as_ai(): void
    {
        $this->actor(); $this->broker(['output_text'=>'{"recommendations":[{"priority":"high","title":"<script>x</script>","reason":"x"}]}']);
        $this->postJson('/assessor/gabinetes/1/inteligencia')->assertOk()
            ->assertJsonPath('data.source','local_rules')->assertJsonPath('data.fallback_reason','invalid_response');
    }

    public function test_response_for_another_project_is_rejected(): void
    {
        $this->actor(); $this->broker(['project_id'=>'other']);
        $this->postJson('/assessor/gabinetes/1/inteligencia')->assertOk()->assertJsonPath('data.source','local_rules');
    }

    public function test_plain_http_configuration_never_transmits_token(): void
    {
        $this->actor(); config(['assessor_ai.broker_url'=>'http://core.test/execute']);
        $this->postJson('/assessor/gabinetes/1/inteligencia')->assertOk()->assertJsonPath('data.fallback_reason','not_configured');
        Http::assertNothingSent();
    }
}
