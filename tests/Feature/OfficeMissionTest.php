<?php

namespace Tests\Feature;

use App\Models\OfficeMission;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class OfficeMissionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.env' => 'homologation', 'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('admin');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('office_missions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('created_by');
            $table->string('domain', 3);
            $table->string('title', 180);
            $table->text('description');
            $table->string('status', 40);
            $table->string('priority', 12);
            $table->uuid('idempotency_key')->unique();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['created_by', 'domain', 'created_at']);
        });
        Schema::create('office_mission_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('mission_id');
            $table->foreignId('actor_id');
            $table->string('event_type', 40);
            $table->string('from_status', 40)->nullable();
            $table->string('to_status', 40);
            $table->uuid('request_id')->unique();
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->index(['mission_id', 'created_at']);
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('office_mission_events');
        Schema::dropIfExists('office_missions');
        Schema::dropIfExists('users');
        parent::tearDown();
    }

    private function admin(string $email = 'admin@example.test'): User
    {
        return User::create([
            'name' => 'Office Admin',
            'email' => $email,
            'password' => 'test-password',
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_register_mission_and_read_persisted_history(): void
    {
        $admin = $this->admin();
        $key = (string) Str::uuid();
        $response = $this->actingAs($admin)->post(route('cockpit.escritorio-agentes.missoes.store'), [
            'domain' => 'DEV',
            'title' => 'Corrigir fluxo de cadastro',
            'description' => 'Corrigir validação e registrar resultado esperado.',
            'priority' => 'high',
            'idempotency_key' => $key,
        ]);

        $response->assertRedirect(route('cockpit.escritorio-agentes.index'));
        $this->assertDatabaseHas('office_missions', [
            'created_by' => $admin->id,
            'domain' => 'DEV',
            'title' => 'Corrigir fluxo de cadastro',
            'status' => 'planned',
            'idempotency_key' => $key,
        ]);
        $mission = OfficeMission::where('idempotency_key', $key)->firstOrFail();
        $this->assertDatabaseHas('office_mission_events', [
            'mission_id' => $mission->id,
            'actor_id' => $admin->id,
            'event_type' => 'mission_created',
            'to_status' => 'planned',
            'request_id' => $key,
        ]);
        $this->actingAs($admin)->get(route('cockpit.escritorio-agentes.index'))
            ->assertOk()
            ->assertSee('Corrigir fluxo de cadastro')
            ->assertSee('Ver histórico (1)');
    }

    public function test_repeated_idempotency_key_does_not_duplicate_mission_or_event(): void
    {
        $admin = $this->admin();
        $key = (string) Str::uuid();
        $payload = [
            'domain' => 'ECO',
            'title' => 'Revisar campanha ECO',
            'description' => 'Revisar o calendário editorial e o resultado esperado.',
            'priority' => 'normal',
            'idempotency_key' => $key,
        ];
        $this->actingAs($admin)->post(route('cockpit.escritorio-agentes.missoes.store'), $payload)->assertRedirect();
        $this->actingAs($admin)->post(route('cockpit.escritorio-agentes.missoes.store'), $payload)->assertRedirect();
        $this->assertSame(1, OfficeMission::where('idempotency_key', $key)->count());
        $this->assertSame(1, \App\Models\OfficeMissionEvent::where('request_id', $key)->count());
    }

    public function test_client_cannot_access_office_or_create_missions(): void
    {
        $client = User::create([
            'name' => 'Client',
            'email' => 'client@example.test',
            'password' => 'test-password',
            'role' => 'client',
            'is_active' => true,
        ]);
        $this->actingAs($client)->get(route('cockpit.escritorio-agentes.index'))->assertForbidden();
        $this->actingAs($client)->post(route('cockpit.escritorio-agentes.missoes.store'), [
            'domain' => 'DEV',
            'title' => 'Blocked mission',
            'description' => 'Must not be registered.',
            'priority' => 'normal',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertForbidden();
        $this->assertSame(0, OfficeMission::count());
    }

    public function test_mission_history_is_scoped_to_creator(): void
    {
        $owner = $this->admin();
        $other = $this->admin('other@example.test');
        OfficeMission::create([
            'id' => (string) Str::uuid(),
            'created_by' => $other->id,
            'domain' => 'DEV',
            'title' => 'Private mission',
            'description' => 'Must not leak to another operator.',
            'status' => 'planned',
            'priority' => 'normal',
            'idempotency_key' => (string) Str::uuid(),
        ]);
        $this->actingAs($owner)->get(route('cockpit.escritorio-agentes.index'))
            ->assertOk()
            ->assertDontSee('Private mission');
    }

    public function test_administrative_minutes_workflow_registers_structured_review_data(): void
    {
        $admin = $this->admin();
        $key = (string) Str::uuid();

        $this->actingAs($admin)->post(route('cockpit.escritorio-agentes.atas.store'), [
            'meeting_title' => 'Reunião semanal da operação',
            'notes' => 'A equipe revisou prioridades, riscos e próximos passos da operação.',
            'decisions' => ['Publicar o relatório até sexta-feira.'],
            'open_items' => ['Confirmar responsável pela revisão final.'],
            'priority' => 'high',
            'idempotency_key' => $key,
        ])->assertRedirect(route('cockpit.escritorio-agentes.index'));

        $mission = OfficeMission::where('idempotency_key', $key)->firstOrFail();
        $this->assertSame('administrativo', $mission->metadata['department']);
        $this->assertSame('meeting_minutes', $mission->metadata['workflow']);
        $this->assertSame('awaiting_manager_review', $mission->metadata['review_status']);
        $this->assertDatabaseHas('office_mission_events', [
            'mission_id' => $mission->id,
            'event_type' => 'minutes_requested',
            'request_id' => $key,
        ]);
    }

    public function test_manager_can_approve_administrative_minutes_once(): void
    {
        $admin = $this->admin();
        $key = (string) Str::uuid();
        $this->actingAs($admin)->post(route('cockpit.escritorio-agentes.atas.store'), [
            'meeting_title' => 'Reunião para aprovação',
            'notes' => 'As anotações da reunião têm conteúdo suficiente para a ata.',
            'priority' => 'normal',
            'idempotency_key' => $key,
        ]);
        $mission = OfficeMission::where('idempotency_key', $key)->firstOrFail();

        $this->actingAs($admin)->patch(route('cockpit.escritorio-agentes.missoes.review', $mission), [
            'decision' => 'approve',
            'note' => 'Ata conferida pelo gestor.',
        ])->assertRedirect(route('cockpit.escritorio-agentes.index'));

        $mission->refresh();
        $this->assertSame('completed', $mission->status);
        $this->assertSame('approved', $mission->metadata['review_status']);
        $this->assertDatabaseHas('office_mission_events', [
            'mission_id' => $mission->id,
            'event_type' => 'minutes_approved',
            'to_status' => 'completed',
        ]);
        $this->actingAs($admin)->patch(route('cockpit.escritorio-agentes.missoes.review', $mission), [
            'decision' => 'approve',
        ])->assertStatus(409);
    }

    public function test_manager_can_review_again_after_requesting_changes(): void
    {
        $admin = $this->admin();
        $key = (string) Str::uuid();
        $this->actingAs($admin)->post(route('cockpit.escritorio-agentes.atas.store'), [
            'meeting_title' => 'Reunião para ajustes',
            'notes' => 'As anotações precisam ser revistas antes da aprovação final.',
            'priority' => 'normal',
            'idempotency_key' => $key,
        ]);
        $mission = OfficeMission::where('idempotency_key', $key)->firstOrFail();

        $this->actingAs($admin)->patch(route('cockpit.escritorio-agentes.missoes.review', $mission), [
            'decision' => 'request_changes',
            'note' => 'Incluir o responsável pela decisão.',
        ])->assertRedirect();
        $this->actingAs($admin)->patch(route('cockpit.escritorio-agentes.missoes.review', $mission), [
            'decision' => 'approve',
            'note' => 'Ajuste conferido.',
        ])->assertRedirect();

        $this->assertSame('completed', $mission->refresh()->status);
        $this->assertSame(3, $mission->events()->count());
    }

    public function test_supervised_minutes_result_returns_mission_to_manager_review(): void
    {
        $admin = $this->admin();
        $key = (string) Str::uuid();
        $this->actingAs($admin)->post(route('cockpit.escritorio-agentes.atas.store'), [
            'meeting_title' => 'Reunião com resultado',
            'notes' => 'Anotações da reunião para produzir uma ata supervisionada.',
            'priority' => 'normal',
            'idempotency_key' => $key,
        ]);
        $mission = OfficeMission::where('idempotency_key', $key)->firstOrFail();

        $this->actingAs($admin)->put(route('cockpit.escritorio-agentes.missoes.result.store', $mission), [
            'minutes_text' => 'A reunião decidiu manter o plano atual e revisar os indicadores na próxima semana.',
            'unresolved_items' => ['Confirmar a pessoa responsável pelo indicador.'],
            'agent_name' => 'Assistente Administrativo IA',
        ])->assertRedirect();

        $mission->refresh();
        $this->assertSame('awaiting_manager_review', $mission->metadata['review_status']);
        $this->assertSame('submitted', $mission->metadata['result_status']);
        $this->assertSame('Assistente Administrativo IA', $mission->metadata['agent_name']);
        $this->assertDatabaseHas('office_mission_events', [
            'mission_id' => $mission->id,
            'event_type' => 'minutes_result_submitted',
        ]);
    }

    public function test_new_result_version_preserves_previous_minutes_after_requested_changes(): void
    {
        $admin = $this->admin();
        $key = (string) Str::uuid();
        $this->actingAs($admin)->post(route('cockpit.escritorio-agentes.atas.store'), [
            'meeting_title' => 'Reunião com versões',
            'notes' => 'Anotações suficientes para gerar a primeira versão da ata.',
            'priority' => 'normal',
            'idempotency_key' => $key,
        ]);
        $mission = OfficeMission::where('idempotency_key', $key)->firstOrFail();
        $route = fn () => route('cockpit.escritorio-agentes.missoes.result.store', $mission);

        $this->actingAs($admin)->put($route(), [
            'minutes_text' => 'Primeira versão da ata com a decisão inicial registrada.',
            'agent_name' => 'Assistente Administrativo IA',
        ]);
        $this->actingAs($admin)->patch(route('cockpit.escritorio-agentes.missoes.review', $mission), [
            'decision' => 'request_changes',
        ]);
        $this->actingAs($admin)->put($route(), [
            'minutes_text' => 'Segunda versão corrigida da ata com a pendência detalhada.',
            'agent_name' => 'Assistente Administrativo IA',
        ]);

        $mission->refresh();
        $this->assertSame(2, $mission->metadata['result_version']);
        $this->assertCount(1, $mission->metadata['result_history']);
        $this->assertSame('Primeira versão da ata com a decisão inicial registrada.', $mission->metadata['result_history'][0]['minutes_text']);
    }

    public function test_approval_records_the_result_version(): void
    {
        $admin = $this->admin();
        $key = (string) Str::uuid();
        $this->actingAs($admin)->post(route('cockpit.escritorio-agentes.atas.store'), [
            'meeting_title' => 'Reunião versão aprovada',
            'notes' => 'Anotações para validar a versão aprovada da ata.',
            'priority' => 'normal',
            'idempotency_key' => $key,
        ]);
        $mission = OfficeMission::where('idempotency_key', $key)->firstOrFail();
        $this->actingAs($admin)->put(route('cockpit.escritorio-agentes.missoes.result.store', $mission), [
            'minutes_text' => 'Versão que será aprovada pelo gestor após conferência.',
            'agent_name' => 'Assistente Administrativo IA',
        ]);
        $this->actingAs($admin)->patch(route('cockpit.escritorio-agentes.missoes.review', $mission), ['decision' => 'approve']);

        $this->assertSame(1, $mission->refresh()->metadata['approved_result_version']);
        $this->assertSame(1, $mission->events()->where('event_type', 'minutes_approved')->first()->payload['approved_result_version']);
    }
}
