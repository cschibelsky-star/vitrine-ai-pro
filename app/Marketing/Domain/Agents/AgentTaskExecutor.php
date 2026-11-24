<?php

/**
 * O QUE É: o executor de tarefas dos agentes — quem faz o agente TRABALHAR de verdade.
 * COMO FUNCIONA: recebe a tarefa, descobre o agente responsável e o modelo dele,
 * monta o contexto do Escritório e chama o provedor.
 * FUNÇÃO NO SISTEMA: ⭐⭐ a ponte entre o Escritório Virtual e as IAs.
 *
 * ⭐⭐ E O MAIS IMPORTANTE — O QUE ESTE SERVIÇO NÃO FAZ:
 *
 *   Ele NÃO entrega a tarefa. Ele NÃO muda status. Ele NÃO registra decisão.
 *
 *   O que ele faz é produzir TEXTO. Quem entrega para a supervisão é o
 *   MarketingTaskViaGate, e a entrega vai para `awaiting_via_review` como sempre.
 *   ⭐ Ou seja: ⭐ NENHUMA IA CONSEGUE SE AUTOAPROVAR. ⭐
 *
 *   ⭐ E ESSA SEPARAÇÃO É ESTRUTURAL, NÃO UMA CONVENÇÃO: o executor não recebe a
 *   porta que grava a decisão. Mesmo que alguém alterasse o código dele, ele
 *   não teria como registrar uma aprovação — porque não é lá que a aprovação é
 *   gravada, e o gate exige um status que só o serviço da VIA pode mover.
 *
 * ⭐ O MODELO DE CADA AGENTE: vem do agente, que vem da definição persistida.
 * É por isso que trocar de IA para um agente é mexer em UM registro — e não em
 * código.
 */

declare(strict_types=1);

namespace App\Marketing\Domain\Agents;

use App\Marketing\Domain\Tasks\TaskStatus;
use App\Marketing\Infrastructure\Persistence\Models\MarketingCampaignTask;
use RuntimeException;

final class AgentTaskExecutor
{
    public function __construct(
        private readonly AgentRegistry $registry,
        private readonly ?AgentModelProvider $provider,
    ) {
    }

    /**
     * ⭐ Executa a tarefa e devolve o texto produzido.
     *
     * @param  array<string, mixed>  $overrides  Ajustes do contexto (departamento, etapa).
     */
    public function execute(MarketingCampaignTask $task, array $overrides = []): AgentModelResult
    {
        $agentId = (string) ($task->agent_definition_id ?? '');

        if ($agentId === '') {
            throw new RuntimeException('A tarefa não possui agente responsável.');
        }

        $agent = $this->registry->get($agentId);
        $model = $this->resolveModel($agentId, $agent);

        if ($this->provider === null) {
            throw new RuntimeException(
                'Nenhum provedor de IA está configurado. Defina IA_PROVEDOR, IA_URL_BASE, IA_CHAVE e IA_MODELOS.'
            );
        }

        if (! $this->provider->supports($model)) {
            throw new RuntimeException(sprintf(
                'Provedor [%s] não possui o modelo [%s] configurado para o agente [%s].',
                $this->provider->name(),
                $model,
                $agentId,
            ));
        }

        $context = [
            'agent_name' => (string) ($agent['name'] ?? $agentId),
            'department' => (string) ($overrides['department'] ?? 'operacional'),
            'step' => (string) ($overrides['step'] ?? (string) $task->status),
            'task_title' => (string) $task->task_key,
            'rules' => OpenAiCompatibleModelProvider::defaultRules(),
            'previous_via_note' => $this->previousViaNote($task),
        ];

        return $this->provider->execute(
            $model,
            (string) $task->task_key,
            $context,
        );
    }

    /**
     * ⭐ O MODELO DO AGENTE.
     *
     * Vem da definição do agente (campo `model`). Se o agente não declarar um
     * modelo, cai no padrão da configuração — assim dá para rodar a fábrica com
     * um modelo só e ir ajustando agente por agente.
     *
     * @param  array<string, mixed>  $agent
     */
    private function resolveModel(string $agentId, array $agent): string
    {
        $model = $agent['model'] ?? null;

        if (is_string($model) && $model !== '') {
            return $model;
        }

        $padrao = config('marketing_agents.default_model');

        if (! is_string($padrao) || $padrao === '') {
            throw new RuntimeException(sprintf(
                'O agente [%s] não declara modelo e não há modelo padrão configurado.',
                $agentId,
            ));
        }

        return $padrao;
    }

    /**
     * ⭐ O PARECER DA VIA NA RODADA ANTERIOR.
     *
     * É o que permite ao mesmo agente corrigir especificamente o que a VIA
     * apontou, em vez de refazer tudo do zero. Vem do `via_note` da tarefa.
     */
    private function previousViaNote(MarketingCampaignTask $task): ?string
    {
        $note = $task->via_note;

        if (! is_string($note) || trim($note) === '') {
            return null;
        }

        return $note;
    }
}
