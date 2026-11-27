<?php

/**
 * O QUE É: o adaptador Avello — o executor inicial de inferência do Escritório.
 * COMO FUNCIONA: monta o envelope normalizado, chama o transporte injetado e
 * valida a resposta antes de devolvê-la.
 * FUNÇÃO NO SISTEMA: ⭐⭐ o PRIMEIRO executor da fábrica, e a ponte para o Avello.
 *
 * ⭐⭐ O CONTRATO VEM DO PR #172 (`avello_adapter.py`), e é o mesmo:
 *
 *   - envelope com mission_id, domínio, tenant_id, modelo, prompt e request_id;
 *   - resposta com status completed, provider avello, o MESMO mission_id e modelo,
 *     execution_id e evidence_ref;
 *   - ⭐ TRANSPORT INJETADO e DESLIGADO por padrão: ⭐ nenhuma chamada de rede
 *     acontece sem um transporte explicitamente autorizado.
 *   - ⭐ FAIL CLOSED: ⭐ qualquer coisa fora do esperado é erro, nunca sucesso.
 *
 * ⭐⭐ E O QUE A AUDITORIA APONTOU, E ESTE ARQUIVO RESPONDE:
 *
 *   1. "Avello ausente do fluxo inicial —.descumprimento da arquitetura
 *      aprovada." ⭐ Este adaptador é a implementação disso: o Avello é o
 *      executor inicial, e o `RoutedModelProvider` o inclui na ordem.
 *
 *   2. "Credencial do Roteia utilizada como alternativa genérica para outros
 *      provedores." ⭐ Aqui não há credencial genérica: ⭐ cada provedor tem a
 *      sua, e o Avello tem a DELE (`AVELLO_*`), nunca a de outro.
 *
 *   3. "Falha de persistência não impede retorno de sucesso." ⭐ O adaptador
 *      exige a evidência (`evidence_ref`) antes de devolver — ⭐ sem evidência
 *      não há entrega, porque ⭐ entrega sem evidência não pode ser julgada
 *      pela VIA.
 *
 * ⭐⭐ E A REGRA DA VIA CONTINUA VALENDO: o adaptador devolve TEXTO e EVIDÊNCIA.
 * ⭐ Ele não registra decisão, não muda status, não publica. ⭐ Quem entrega
 * para a supervisão é o `MarketingTaskViaGate`.
 */

declare(strict_types=1);

namespace App\Marketing\Domain\Agents;

use App\Services\Ai\AiRoutingService;
use RuntimeException;

final class AvelloExecutionAdapter
{
    /**
     * ⭐ O TRANSPORTE — a chamada de verdade ao Avello.
     *
     * ⭐ É INJETADO, e é opcional. ⭐ Sem ele, o adaptador é desativado — e é
     * assim que ele nasce: ⭐ nenhuma chamada de rede acontece por acidente.
     *
     * ⭐ A assinatura segue o contrato do PR #172: recebe o envelope normalizado
     * e devolve o dicionário da resposta.
     *
     * @var (callable(array<string, mixed>): array<string, mixed>)|null
     */
    private $transport;

    /**
     * @param  (callable(array<string, mixed>): array<string, mixed>)|null  $transport
     */
    public function __construct(
        private readonly AiRoutingService $routing,
        $transport = null,
        private readonly bool $enabled = false,
    ) {
        $this->transport = $transport;
    }

    public function name(): string
    {
        return 'avello';
    }

    /**
     * ⭐ O AVELLO É O EXECUTOR INICIAL — então ele responde por toda capability.
     */
    public function supports(string $model): bool
    {
        return $this->enabled && $this->transport !== null;
    }

    /**
     * ⭐ Executa a tarefa pelo Avello e devolve o texto com a evidência.
     *
     * @param  array<string, mixed>  $context
     */
    public function execute(string $model, string $instruction, array $context): AgentModelResult
    {
        if (! $this->enabled || $this->transport === null) {
            throw new RuntimeException(
                'Avello está desativado. Habilite-o com um transporte autenticado e autorização explícita de custo.'
            );
        }

        // ⭐ O ENVELOPE — o mesmo contrato do PR #172.
        $envelope = [
            'mission_id' => (string) ($context['mission_id'] ?? $context['task_title'] ?? ''),
            'domain' => (string) ($context['domain'] ?? 'DEV'),
            'tenant_id' => $context['tenant_id'] ?? null,
            'model' => $model,
            'prompt' => $this->buildPrompt($instruction, $context),
            'request_id' => (string) ($context['request_id'] ?? ''),
        ];

        if ($envelope['mission_id'] === '' || $envelope['request_id'] === '') {
            throw new RuntimeException('Avello: missão ou request_id ausente.');
        }

        try {
            $resposta = ($this->transport)($envelope);
        } catch (\Throwable $e) {
            throw new RuntimeException('Avello: falha do transporte.', 0, $e);
        }

        // ⭐⭐ A VALIDAÇÃO DA RESPOSTA — fail closed.
        //
        // ⭐ Cada item abaixo é uma checagem do contrato do PR #172. ⭐ Qualquer
        // uma que falhe é um ERRO — nunca um texto vazio entregue como se fosse
        // trabalho do agente.
        if (! is_array($resposta) || ($resposta['status'] ?? null) !== 'completed') {
            throw new RuntimeException('Avello: execução não concluída.');
        }

        if (($resposta['provider'] ?? null) !== 'avello') {
            throw new RuntimeException('Avello: resposta de outro provedor.');
        }

        if (($resposta['mission_id'] ?? null) !== $envelope['mission_id']) {
            throw new RuntimeException('Avello: identidade da missão não confere.');
        }

        if (($resposta['model'] ?? null) !== $model) {
            throw new RuntimeException('Avello: modelo divergente.');
        }

        // ⭐⭐ A EVIDÊNCIA É OBRIGATÓRIA.
        //
        // ⭐ Sem `evidence_ref` e sem `execution_id`, não há como auditar a
        // execução — e a VIA audita. ⭐ Uma entrega sem evidência não pode ser
        // julgada, então ela nem deve ser produzida.
        $executionId = trim((string) ($resposta['execution_id'] ?? ''));
        $evidenceRef = trim((string) ($resposta['evidence_ref'] ?? ''));

        if ($executionId === '' || $evidenceRef === '') {
            throw new RuntimeException('Avello: resposta sem evidência.');
        }

        $texto = trim((string) ($resposta['content'] ?? $resposta['text'] ?? ''));

        if ($texto === '') {
            throw new RuntimeException('Avello: resposta sem conteúdo.');
        }

        return new AgentModelResult(
            text: $texto,
            modelUsed: (string) $resposta['model'],
            inputTokens: (int) ($resposta['input_tokens'] ?? 0),
            outputTokens: (int) ($resposta['output_tokens'] ?? 0),

            // ⭐ O CUSTO vem do Avello quando ele informa. ⭐ Sem tarifário, o
            // custo NÃO é inventado: fica zero, como em toda a plataforma.
            costCents: (int) round(((float) ($resposta['cost'] ?? 0)) * 100),
            durationMs: (int) ($resposta['duration_ms'] ?? 0),
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function buildPrompt(string $instruction, array $context): string
    {
        $sections = [];

        $sections[] = sprintf(
            'Você é %s, um agente do Escritório Virtual (%s).',
            (string) ($context['agent_name'] ?? 'Agente'),
            (string) ($context['department'] ?? 'operacional'),
        );

        if (! empty($context['step'])) {
            $sections[] = 'Etapa atual: '.(string) $context['step'].'.';
        }

        $sections[] = '## SUA TAREFA';
        $sections[] = (string) ($context['task_title'] ?? $instruction);
        $sections[] = '';
        $sections[] = $instruction;

        if (! empty($context['previous_via_note'])) {
            $sections[] = '## O QUE A VIA REPROVOU NA RODADA ANTERIOR';
            $sections[] = 'Corrija especificamente o que foi apontado. Não refaça do zero o que já está bom.';
            $sections[] = (string) $context['previous_via_note'];
        }

        $sections[] = '';
        $sections[] = '## REGRAS DO ESCRITÓRIO';
        $sections[] = (string) ($context['rules'] ?? OpenAiCompatibleModelProvider::defaultRules());

        return implode("\n", $sections);
    }
}
