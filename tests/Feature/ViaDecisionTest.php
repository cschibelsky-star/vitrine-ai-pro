<?php

/**
 * O QUE É: os testes da DECISÃO DA VIA — a camada supervisora do Escritório.
 * COMO FUNCIONA: monta uma missão com entrega, tenta decidir por caminhos
 * diferentes e verifica o que é aceito e o que é recusado.
 * FUNÇÃO NO SISTEMA: ⭐⭐⭐ COMPROVAR QUE A SUPERVISÃO NÃO É DECORATIVA.
 *
 * ⭐⭐⭐ POR QUE ESTE ARQUIVO EXISTE:
 *
 * A auditoria do PR #183 apontou, com razão, que os testes de reflexão (que
 * verificam a AUSÊNCIA da porta de decisão na assinatura do executor) provam
 * apenas uma coisa: que o executor não tem a porta. ⭐⭐ Eles NÃO provam que a
 * decisão vem de um supervisor AUTENTICADO e INDEPENDENTE do trabalho julgado.
 *
 * ⭐⭐ Um admin que produz a entrega e a aprova no mesmo clique passaria por
 * todos os testes que existiam. ⭐⭐⭐ Estes testes são a lacuna preenchida.
 */

namespace Tests\Feature;

use App\Marketing\Domain\Agents\ViaDecisionService;
use App\Models\OfficeMission;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class ViaDecisionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['app.env' => 'homologation', 'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('admin');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('office_missions', function (Blueprint $table): void {
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
        });
        Schema::create('office_mission_events', function (Blueprint $table): void {
            $table->id();
            $table->uuid('mission_id');
            $table->foreignId('actor_id');
            $table->string('event_type', 40);
            $table->string('from_status', 40)->nullable();
            $table->string('to_status', 40);
            $table->uuid('request_id')->unique();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('office_mission_events');
        Schema::dropIfExists('office_missions');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    private function usuario(string $role = 'admin', bool $ativo = true): User
    {
        $usuario = new User();
        $usuario->forceFill([
            'name' => 'Usuario '.Str::random(4),
            'email' => Str::random(10).'@teste.local',
            'password' => 'x',
            'role' => $role,
            'is_active' => $ativo,
        ])->save();

        return $usuario;
    }

    /**
     * ⭐ Uma missão com entrega registrada e aguardando a VIA.
     *
     * @param  array<string, mixed>  $metadata
     */
    private function missaoEmRevisao(User $criador, array $metadata = []): OfficeMission
    {
        $missao = new OfficeMission();
        $missao->forceFill([
            'id' => (string) Str::uuid(),
            'created_by' => $criador->id,
            'domain' => 'ECO',
            'title' => 'Ata da reunião de supervisão',
            'description' => 'Registrar a ata do encontro.',
            'status' => 'planned',
            'priority' => 'normal',
            'idempotency_key' => (string) Str::uuid(),
            'metadata' => array_merge([
                'workflow' => 'meeting_minutes',
                'review_status' => 'awaiting_manager_review',
                'result_version' => 1,
                'minutes_text' => 'A ata produzida pelo agente.',
                'result_submitted_by' => $criador->id,
            ], $metadata),
        ])->save();

        return $missao;
    }

    // =========================================================================
    // ⭐⭐⭐ A INDEPENDÊNCIA — quem produz não pode julgar
    // =========================================================================

    /**
     * ⭐⭐⭐ O SUPERVISOR NÃO PODE SER QUEM PRODUZIU A ENTREGA.
     *
     * ⭐⭐⭐ Este é o teste que fecha a lacuna apontada pela auditoria. ⭐ Antes,
     * o mesmo usuário que registrava o resultado podia revisar — e nada o
     * impedia.
     */
    public function test_o_supervisor_nao_pode_aprovar_uma_entrega_que_ele_produziu(): void
    {
        $supervisor = $this->usuario();
        $missao = $this->missaoEmRevisao($supervisor);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('não pode ser o supervisor de uma entrega que o próprio supervisor produziu');

        app(ViaDecisionService::class)->decide(
            mission: $missao,
            aprovado: true,
            parecer: 'Aprovado.',
            supervisorId: (int) $supervisor->id,
            atorId: (int) $supervisor->id,
        );
    }

    /**
     * ⭐⭐ A REPROVAÇÃO TAMBÉM É BLOQUEADA — ⭐ senão o supervisor usaria o
     * caminho da reprovação para devolver a própria entrega.
     */
    public function test_o_supervisor_nao_pode_reprovar_uma_entrega_que_ele_produziu(): void
    {
        $supervisor = $this->usuario();
        $missao = $this->missaoEmRevisao($supervisor);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('não pode ser o supervisor');

        app(ViaDecisionService::class)->decide(
            mission: $missao,
            aprovado: false,
            parecer: 'Devolvido.',
            supervisorId: (int) $supervisor->id,
        );
    }

    /**
     * ⭐⭐ SEM ENTREGA REGISTRADA, O SUPERVISOR NÃO PODE SER QUEM CRIOU A MISSÃO.
     *
     * ⭐⭐ Quem define o escopo do trabalho não pode ser o único a approve o
     * próprio escopo.
     */
    public function test_o_supervisor_nao_pode_aprovar_a_missao_que_ele_criou(): void
    {
        $criador = $this->usuario();
        $missao = $this->missaoEmRevisao($criador, ['result_submitted_by' => null]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('não pode ser o supervisor da própria missão que criou');

        app(ViaDecisionService::class)->decide(
            mission: $missao,
            aprovado: true,
            parecer: 'Aprovado.',
            supervisorId: (int) $criador->id,
        );
    }

    /**
     * ⭐⭐⭐ UM SUPERVISOR INDEPENDENTE APROVA — e a decisão fica registrada.
     */
    public function test_um_supervisor_independente_aprova_e_a_decisao_fica_registrada(): void
    {
        $produtor = $this->usuario();
        $supervisor = $this->usuario();
        $missao = $this->missaoEmRevisao($produtor);

        $resultado = app(ViaDecisionService::class)->decide(
            mission: $missao,
            aprovado: true,
            parecer: 'Evidência conferida.',
            supervisorId: (int) $supervisor->id,
            atorId: (int) $supervisor->id,
        );

        $this->assertSame('completed', $resultado->status);
        $this->assertSame('approved', $resultado->metadata['review_status']);
        $this->assertSame($supervisor->id, $resultado->metadata['via_supervisor_id']);
        $this->assertSame($supervisor->id, $resultado->metadata['reviewed_by']);
        $this->assertSame(1, $resultado->metadata['approved_result_version']);

        // ⭐⭐ A TRILHA DE AUDITORIA — com quem decidiu e quem produziu.
        $evento = $missao->events()->first();
        $this->assertNotNull($evento);
        $this->assertSame('via_approved', $evento->event_type);
        $this->assertSame($supervisor->id, $evento->payload['supervisor_id']);
        $this->assertSame($produtor->id, $evento->payload['producer_id']);
        $this->assertTrue($evento->payload['handoff_released']);
    }

    /**
     * ⭐⭐⭐ A REPROVAÇÃO DEVOLVE AO MESMO AGENTE, NA PRÓXIMA RODADA.
     *
     * ⭐⭐⭐ ESTE É O CORAÇÃO DA REGRA DO ESCRITÓRIO, E ESTE TESTE A PROVA:
     * "caso não seja aprovado, volta ao AGENTE novamente até a aprovação".
     */
    public function test_a_reprovacao_devolve_ao_mesmo_agente_na_proxima_rodada(): void
    {
        $produtor = $this->usuario();
        $supervisor = $this->usuario();
        $missao = $this->missaoEmRevisao($produtor);

        $resultado = app(ViaDecisionService::class)->decide(
            mission: $missao,
            aprovado: false,
            parecer: 'Faltou a evidência.',
            supervisorId: (int) $supervisor->id,
        );

        $this->assertSame('planned', $resultado->status);
        $this->assertSame('changes_requested', $resultado->metadata['review_status']);

        // ⭐⭐ O RESPONSÁVEL NÃO MUDA — a tarefa volta ao MESMO agente.
        $this->assertSame($produtor->id, $resultado->metadata['result_submitted_by']);

        // ⭐⭐ E A RODADA SOBE, para ficar visível quantas vezes voltou.
        $this->assertSame(2, $resultado->metadata['revision_round']);

        $evento = $missao->events()->first();
        $this->assertSame('via_changes_requested', $evento->event_type);
        $this->assertFalse($evento->payload['handoff_released']);
        $this->assertSame(2, $evento->payload['revision_round']);
    }

    /**
     * ⭐⭐ A VIA SÓ DECIDE O QUE ESTÁ EM REVISÃO.
     */
    public function test_a_via_nao_decide_uma_entrega_que_nao_esta_em_revisao(): void
    {
        $produtor = $this->usuario();
        $supervisor = $this->usuario();
        $missao = $this->missaoEmRevisao($produtor, ['review_status' => 'approved']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('não está em revisão da VIA');

        app(ViaDecisionService::class)->decide(
            mission: $missao,
            aprovado: true,
            parecer: 'Já aprovada.',
            supervisorId: (int) $supervisor->id,
        );
    }

    /**
     * ⭐⭐ A DECISÃO NÃO MUDA O RESULTADO APROVADO — ⭐ o que foi aprovado é a
     * VERSÃO que foi julgada.
     */
    public function test_a_aprovacao_prende_a_versao_julgada(): void
    {
        $produtor = $this->usuario();
        $supervisor = $this->usuario();
        $missao = $this->missaoEmRevisao($produtor, ['result_version' => 3]);

        $resultado = app(ViaDecisionService::class)->decide(
            mission: $missao,
            aprovado: true,
            parecer: 'Versão 3 conferida.',
            supervisorId: (int) $supervisor->id,
        );

        $this->assertSame(3, $resultado->metadata['approved_result_version']);
    }
}
