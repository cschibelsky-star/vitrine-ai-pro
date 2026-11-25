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

        // ⭐⭐ A ORDEM DAS CHEKAGENS IMPORTA.
        //
        // ⭐ O PROVEDOR É VERIFICADO PRIMEIRO, e não o modelo. A razão é a
        // mensagem que o usuário recebe:
        //
        //   - Sem provedor: "Nenhum provedor de IA está configurado" — que é a
        //     CAUSA RAIZ. Dizer "não há modelo padrão" seria levar o usuário a
        //     caçar um modelo quando o que falta é a IA inteira.
        //   - Com provedor, mas sem modelo: aí sim é o modelo que falta, e a
        //     mensagem aponta o agente e o modelo pedido.
        //
        // ⭐ Todas as mensagens citam as variáveis do IA Center, porque são as
        // que valem — e o Escritório não tem variáveis próprias.
        if ($this->provider === null) {
            throw new RuntimeException(
                'Nenhum provedor de IA está configurado. Defina ROTEIA_BASE_URL, ROTEIA_API_KEY e ESCRITORIO_IA_MODELOS.'
            );
        }

        $model = $this->resolveModel($agentId, $agent);

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
     * Ordem de resolução:
     *   1. o modelo que o AGENTE declara (`marketing_agents.agents.<id>.model`);
     *   2. `ESCRITORIO_MODEL_PADRAO` — o padrão do Escritório;
     *   3. `ROTEIA_CHAT_MODEL` — o padrão do gateway, que a plataforma já usa.
     *
     * ⭐ A ordem importa: o agente é o mais específico, então ele vence. O padrão
     * do gateway é o último recurso — assim o Escritório funciona mesmo sem
     * configuração própria, sem criar uma segunda convenção.
     *
     * @param  array<string, mixed>  $agent
     */
    private function resolveModel(string $agentId, array $agent): string
    {
        $model = $agent['model'] ?? null;

        if (is_string($model) && $model !== '') {
            return $model;
        }

        foreach (
            [
                config('marketing_agents.default_model'),
                config('marketing_agents.ai.model_default'),
            ] as $padrao
        ) {
            if (is_string($padrao) && $padrao !== '') {
                return $padrao;
            }
        }

        throw new RuntimeException(sprintf(
            'O agente [%s] não declara modelo e não há modelo padrão configurado. Defina ESCRITORIO_MODEL_PADRAO ou ROTEIA_CHAT_MODEL.',
            $agentId,
        ));
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
