<?php

namespace Tests\Feature;

use App\Models\OfficeMission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OfficeMissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.env' => 'homologation']);
    }

    private function admin(string $email = 'admin@example.test'): User
    {
        return User::create([
            'name' => 'Office Admin', 'email' => $email,
            'password' => 'test-password', 'role' => 'admin', 'is_active' => true,
        ]);
    }

    public function test_admin_can_register_mission_and_read_persisted_history(): void
    {
        $admin = $this->admin();
        $key = (string) Str::uuid();
        $response = $this->actingAs($admin)->post(route('cockpit.escritorio-agentes.missoes.store'), [
            'domain' => 'DEV', 'title' => 'Corrigir fluxo de cadastro',
            'description' => 'Corrigir validação e registrar resultado esperado.',
            'priority' => 'high', 'idempotency_key' => $key,
        ]);

        $response->assertRedirect(route('cockpit.escritorio-agentes.index'));
        $this->assertDatabaseHas('office_missions', [
            'created_by' => $admin->id, 'domain' => 'DEV',
            'title' => 'Corrigir fluxo de cadastro', 'status' => 'planned',
            'idempotency_key' => $key,
        ]);
        $mission = OfficeMission::where('idempotency_key', $key)->firstOrFail();
        $this->assertDatabaseHas('office_mission_events', [
            'mission_id' => $mission->id, 'actor_id' => $admin->id,
            'event_type' => 'mission_created', 'to_status' => 'planned', 'request_id' => $key,
        ]);
        $this->actingAs($admin)->get(route('cockpit.escritorio-agentes.index'))
            ->assertOk()->assertSee('Corrigir fluxo de cadastro')->assertSee('Ver histórico (1)');
    }

    public function test_repeated_idempotency_key_does_not_duplicate_mission_or_event(): void
    {
        $admin = $this->admin();
        $key = (string) Str::uuid();
        $payload = ['domain' => 'ECO', 'title' => 'Revisar campanha ECO',
            'description' => 'Revisar o calendário editorial e o resultado esperado.',
            'priority' => 'normal', 'idempotency_key' => $key];
        $this->actingAs($admin)->post(route('cockpit.escritorio-agentes.missoes.store'), $payload)->assertRedirect();
        $this->actingAs($admin)->post(route('cockpit.escritorio-agentes.missoes.store'), $payload)->assertRedirect();
        $this->assertSame(1, OfficeMission::where('idempotency_key', $key)->count());
        $this->assertSame(1, \App\Models\OfficeMissionEvent::where('request_id', $key)->count());
    }

    public function test_client_cannot_access_office_or_create_missions(): void
    {
        $client = User::create(['name'=>'Client','email'=>'client@example.test',
            'password'=>'test-password','role'=>'client','is_active'=>true]);
        $this->actingAs($client)->get(route('cockpit.escritorio-agentes.index'))->assertForbidden();
        $this->actingAs($client)->post(route('cockpit.escritorio-agentes.missoes.store'), [
            'domain'=>'DEV','title'=>'Blocked mission','description'=>'Must not be registered.',
            'priority'=>'normal','idempotency_key'=>(string) Str::uuid(),
        ])->assertForbidden();
        $this->assertSame(0, OfficeMission::count());
    }

    public function test_mission_history_is_scoped_to_creator(): void
    {
        $owner = $this->admin();
        $other = $this->admin('other@example.test');
        OfficeMission::create(['id'=>(string) Str::uuid(),'created_by'=>$other->id,
            'domain'=>'DEV','title'=>'Private mission','description'=>'Must not leak.',
            'status'=>'planned','priority'=>'normal','idempotency_key'=>(string) Str::uuid()]);
        $this->actingAs($owner)->get(route('cockpit.escritorio-agentes.index'))
            ->assertOk()->assertDontSee('Private mission');
    }
}
