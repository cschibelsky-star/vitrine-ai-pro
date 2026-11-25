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
        $agentKey = $this->agentKeyFor($task);

        if ($agentKey === '') {
            throw new RuntimeException('A tarefa não possui agente responsável.');
        }

        $agent = $this->registry->get($agentKey);

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

        $model = $this->resolveModel($agentKey, $agent);

        if (! $this->provider->supports($model)) {
            throw new RuntimeException(sprintf(
                'Provedor [%s] não possui o modelo [%s] configurado para o agente [%s].',
                $this->provider->name(),
                $model,
                $agentKey,
            ));
        }

        $context = [
            'agent_name' => (string) ($agent['name'] ?? $agentKey),
            'department' => (string) ($overrides['department'] ?? 'operacional'),
            'step' => (string) ($overrides['step'] ?? (string) $task->status),
            'task_title' => (string) $task->task_key,
            'rules' => OpenAiCompatibleModelProvider::defaultRules(),
            'previous_via_note' => $this->previousViaNote($task),

            // ⭐ A CAPACIDADE — o que decide QUAL provedor atende.
            //
            // ⭐ Vem do AGENTE (`marketing_agents.agents.<id>.capability`), e é a
            // mesma chave que o `AiRoutingService` usa para escolher o provedor.
            // ⭐ Sem ela, todo agente cairia na capability padrão (`copy`) — e o
            // roteamento não teria como escolher um provedor adequado.
            'capability' => (string) ($agent['capability'] ?? $overrides['capability'] ?? 'copy'),
        ];

        return $this->provider->execute(
            $model,
            (string) $task->task_key,
            $context,
        );
    }

    /**
     * ⭐⭐ A CHAVE DO AGENTE.
     *
     * ⭐⭐ ESTE É O PONTO QUE O EXECUTOR ESTAVA ERRADO.
     *
     * A tarefa guarda `agent_definition_id`, que é o **ID do registro no banco**
     * (um inteiro). Mas o `AgentRegistry` é indexado pela **CHAVE** — `copy_content`,
     * `creative_director` — que é o `agent_key` do registro.
     *
     * ⭐ São dois identificadores diferentes. Usar o ID como chave daria "Marketing
     * agent [42] is not registered" — um erro que só apareceria com tarefa de
     * verdade, nunca no teste com chave fixa.
     *
     * ⭐ E É POR ISSO QUE O `OfficeOperationalStateController` faz
     * `$task->agentDefinition?->agent_key`: é a mesma travessia. ⭐ O Escritório
     * usa o mesmo caminho, e não um atalho.
     */
    private function agentKeyFor(MarketingCampaignTask $task): string
    {
        // ⭐ A definição persistida é a fonte da verdade: ela sabe a chave.
        //
        // ⭐ A relação só é consultada se JÁ vier carregada. Consultar o banco
        // aqui seria uma consulta a cada execução — e, num ambiente onde a
        // tabela ainda não existe, um erro de banco no lugar de uma mensagem
        // clara.
        if ($task->relationLoaded('agentDefinition')) {
            $definicao = $task->getRelation('agentDefinition');

            if ($definicao && ! empty($definicao->agent_key)) {
                return (string) $definicao->agent_key;
            }
        }

        // ⭐ Sem o registro carregado, o próprio ID pode ser a chave — acontece
        // quando a tarefa é criada com o agent_key no lugar do id.
        return (string) ($task->agent_definition_id ?? '');
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
    private function resolveModel(string $agentKey, array $agent): string
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
            $agentKey,
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
