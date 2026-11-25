<?php

/**
 * O QUE É: os testes do adaptador Avello — o executor inicial do Escritório.
 * COMO FUNCIONA: injeta um transporte de mentira e verifica o contrato.
 * FUNÇÃO NO SISTEMA: ⭐ garantir o FAIL CLOSED do adaptador — que é o que o
 * PR #172 estabeleceu e que a auditoria exigiu.
 *
 * ⭐⭐ O QUE ESTES TESTES PROVAM:
 *   1. ⭐ Sem transporte, o adaptador é DESATIVADO — nenhuma chamada acontece.
 *   2. ⭐ A resposta é validada inteira: ⭐ status, provider, mission_id, model.
 *   3. ⭐ ⭐ SEM EVIDÊNCIA, NÃO HÁ ENTREGA — porque a VIA audita a evidência.
 *   4. ⭐ O envelope tem o contrato do PR #172.
 *   5. ⭐ ⭐ O ADAPTADOR NÃO TEM ACESSO À PORTA DE DECISÃO DA VIA.
 */

namespace Tests\Unit\Marketing;

use App\Marketing\Domain\Agents\AgentModelResult;
use App\Marketing\Domain\Agents\AvelloExecutionAdapter;
use App\Services\Ai\AiRoutingService;
use ReflectionClass;
use ReflectionParameter;
use RuntimeException;
use Tests\TestCase;

class AvelloExecutionAdapterTest extends TestCase
{
    /**
     * ⭐ O ROTEADOR DE MENTIRA — o adaptador só precisa da ordem de preferência.
     */
    private function roteador(): AiRoutingService
    {
        return new class extends AiRoutingService
        {
            public function __construct()
            {
                parent::__construct(
                    new \App\Services\Ai\AiExecutionService(),
                    new \App\Services\Ai\AiMediaGenerationService(),
                );
            }
        };
    }

    /**
     * ⭐ UMA RESPOSTA VÁLIDA, conforme o contrato do PR #172.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function respostaValida(array $overrides = []): array
    {
        return array_merge([
            'status' => 'completed',
            'provider' => 'avello',
            'mission_id' => 'missao-1',
            'model' => 'gpt-6.1-sol',
            'execution_id' => 'exec-abc',
            'evidence_ref' => 'evidencia://missao-1/rodada-1',
            'content' => 'A ata elaborada pelo agente.',
            'duration_ms' => 1200,
        ], $overrides);
    }

    // =========================================================================
    // ⭐ O ADAPTADOR NASCE DESATIVADO
    // =========================================================================

    public function test_sem_transporte_o_avello_fica_desativado(): void
    {
        $adaptador = new AvelloExecutionAdapter($this->roteador());

        $this->assertFalse($adaptador->supports('gpt-6.1-sol'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Avello está desativado');

        $adaptador->execute('gpt-6.1-sol', 'elabore a ata', [
            'mission_id' => 'missao-1',
            'request_id' => 'req-1',
        ]);
    }

    public function test_o_transporte_so_e_chamado_quando_habilitado(): void
    {
        $chamadas = 0;

        $adaptador = new AvelloExecutionAdapter(
            routing: $this->roteador(),
            transport: function (array $envelope) use (&$chamadas): array {
                $chamadas++;

                return $this->respostaValida();
            },
            enabled: true,
        );

        $this->assertTrue($adaptador->supports('gpt-6.1-sol'));

        $adaptador->execute('gpt-6.1-sol', 'elabore a ata', [
            'mission_id' => 'missao-1',
            'request_id' => 'req-1',
        ]);

        $this->assertSame(1, $chamadas);
    }

    // =========================================================================
    // ⭐⭐ O ENVELOPE — o contrato do PR #172
    // =========================================================================

    public function test_o_envelope_tem_o_contrato_do_pr_172(): void
    {
        $envelopeRecebido = null;

        $adaptador = new AvelloExecutionAdapter(
            routing: $this->roteador(),
            transport: function (array $envelope) use (&$envelopeRecebido): array {
                $envelopeRecebido = $envelope;

                return $this->respostaValida();
            },
            enabled: true,
        );

        $adaptador->execute('gpt-6.1-sol', 'elabore a ata', [
            'mission_id' => 'missao-1',
            'request_id' => 'req-1',
            'domain' => 'ECO',
            'tenant_id' => 7,
            'agent_name' => 'Agente Motor',
        ]);

        $this->assertIsArray($envelopeRecebido);
        $this->assertSame('missao-1', $envelopeRecebido['mission_id']);
        $this->assertSame('ECO', $envelopeRecebido['domain']);
        $this->assertSame(7, $envelopeRecebido['tenant_id']);
        $this->assertSame('gpt-6.1-sol', $envelopeRecebido['model']);
        $this->assertSame('req-1', $envelopeRecebido['request_id']);
        $this->assertArrayHasKey('prompt', $envelopeRecebido);
    }

    // =========================================================================
    // ⭐⭐ FAIL CLOSED — cada item do contrato é verificado
    // =========================================================================

    public function test_resposta_com_status_diferente_de_completed_falha(): void
    {
        $adaptador = new AvelloExecutionAdapter(
            routing: $this->roteador(),
            transport: fn (array $envelope): array => $this->respostaValida(['status' => 'running']),
            enabled: true,
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('execução não concluída');

        $adaptador->execute('gpt-6.1-sol', 'elabore a ata', [
            'mission_id' => 'missao-1',
            'request_id' => 'req-1',
        ]);
    }

    public function test_resposta_de_outro_provedor_falha(): void
    {
        $adaptador = new AvelloExecutionAdapter(
            routing: $this->roteador(),
            transport: fn (array $envelope): array => $this->respostaValida(['provider' => 'outro']),
            enabled: true,
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('outro provedor');

        $adaptador->execute('gpt-6.1-sol', 'elabore a ata', [
            'mission_id' => 'missao-1',
            'request_id' => 'req-1',
        ]);
    }

    public function test_resposta_de_outra_missao_falha(): void
    {
        $adaptador = new AvelloExecutionAdapter(
            routing: $this->roteador(),
            transport: fn (array $envelope): array => $this->respostaValida(['mission_id' => 'outra-missao']),
            enabled: true,
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('identidade da missão não confere');

        $adaptador->execute('gpt-6.1-sol', 'elabore a ata', [
            'mission_id' => 'missao-1',
            'request_id' => 'req-1',
        ]);
    }

    public function test_resposta_com_outro_modelo_falha(): void
    {
        $adaptador = new AvelloExecutionAdapter(
            routing: $this->roteador(),
            transport: fn (array $envelope): array => $this->respostaValida(['model' => 'outro-modelo']),
            enabled: true,
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('modelo divergente');

        $adaptador->execute('gpt-6.1-sol', 'elabore a ata', [
            'mission_id' => 'missao-1',
            'request_id' => 'req-1',
        ]);
    }

    /**
     * ⭐⭐⭐ SEM EVIDÊNCIA, NÃO HÁ ENTREGA.
     *
     * ⭐⭐ Este é o teste mais importante do arquivo. ⭐ A VIA audita a
     * evidência — então uma entrega sem `evidence_ref` não pode ser julgada.
     * ⭐ Devolvê-la como se fosse trabalho do agente seria devolver algo que
     * a supervisão não consegue verificar.
     */
    public function test_resposta_sem_evidencia_falha(): void
    {
        $adaptador = new AvelloExecutionAdapter(
            routing: $this->roteador(),
            transport: fn (array $envelope): array => $this->respostaValida([
                'evidence_ref' => '',
            ]),
            enabled: true,
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('sem evidência');

        $adaptador->execute('gpt-6.1-sol', 'elabore a ata', [
            'mission_id' => 'missao-1',
            'request_id' => 'req-1',
        ]);
    }

    public function test_resposta_sem_execution_id_falha(): void
    {
        $adaptador = new AvelloExecutionAdapter(
            routing: $this->roteador(),
            transport: fn (array $envelope): array => $this->respostaValida([
                'execution_id' => '',
            ]),
            enabled: true,
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('sem evidência');

        $adaptador->execute('gpt-6.1-sol', 'elabore a ata', [
            'mission_id' => 'missao-1',
            'request_id' => 'req-1',
        ]);
    }

    public function test_falha_do_transporte_vira_erro(): void
    {
        $adaptador = new AvelloExecutionAdapter(
            routing: $this->roteador(),
            transport: function (array $envelope): array {
                throw new \RuntimeException('conexão recusada');
            },
            enabled: true,
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('falha do transporte');

        $adaptador->execute('gpt-6.1-sol', 'elabore a ata', [
            'mission_id' => 'missao-1',
            'request_id' => 'req-1',
        ]);
    }

    public function test_a_execucao_valida_devolve_o_texto_e_o_custo(): void
    {
        $adaptador = new AvelloExecutionAdapter(
            routing: $this->roteador(),
            transport: fn (array $envelope): array => $this->respostaValida([
                'cost' => 0.05,
                'input_tokens' => 100,
                'output_tokens' => 30,
            ]),
            enabled: true,
        );

        $resultado = $adaptador->execute('gpt-6.1-sol', 'elabore a ata', [
            'mission_id' => 'missao-1',
            'request_id' => 'req-1',
        ]);

        $this->assertInstanceOf(AgentModelResult::class, $resultado);
        $this->assertSame('A ata elaborada pelo agente.', $resultado->text);
        $this->assertSame('gpt-6.1-sol', $resultado->modelUsed);
        $this->assertSame(100, $resultado->inputTokens);
        $this->assertSame(30, $resultado->outputTokens);
        $this->assertSame(5, $resultado->costCents);
        $this->assertSame(1200, $resultado->durationMs);
    }

    // =========================================================================
    // ⭐⭐ A VIA CONTINUA GOVERNANDO
    // =========================================================================

    /**
     * ⭐⭐⭐ O AVELLO NÃO TEM ACESSO À PORTA DE DECISÃO DA VIA.
     *
     * ⭐ O adaptador produz texto e evidência. ⭐ Ele não registra decisão,
     * não muda status, não publica. ⭐ Se tivesse a porta, uma IA poderia se
     * autoaprovar — e é exatamente isso que a regra proíbe.
     */
    public function test_o_avello_nao_tem_acesso_a_porta_de_decisao_da_via(): void
    {
        $construtor = (new ReflectionClass(AvelloExecutionAdapter::class))->getConstructor();

        $this->assertNotNull($construtor);

        $tipos = array_map(
            fn (ReflectionParameter $parametro): string => (string) $parametro->getType(),
            $construtor->getParameters(),
        );

        foreach ($tipos as $tipo) {
            $this->assertStringNotContainsString('ViaGate', $tipo);
            $this->assertStringNotContainsString('Decision', $tipo);
        }
    }
}
