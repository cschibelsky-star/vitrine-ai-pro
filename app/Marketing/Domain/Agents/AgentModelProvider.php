<?php

/**
 * O QUE É: o contrato de um provedor de modelo de IA para os agentes do Escritório.
 * COMO FUNCIONA: um adaptador para a API de um provedor. Todos os provedores
 * implementam esta interface, então o Escritório não sabe — e não precisa saber —
 * qual IA está rodando.
 * FUNÇÃO NO SISTEMA: ⭐ permitir que o agente do Escritório use a MESMA camada de
 * IA que o resto da plataforma (IA Center), sem duplicar a integração.
 *
 * ⭐⭐ E POR QUE ISTO NÃO DUPLICA O `AiRoutingService`:
 *
 *   A plataforma JÁ TEM uma camada de IA de produção:
 *   - `AiProvider` (tabela) — os provedores, com slug, status e capacidades;
 *   - `AiRoutingService` — escolhe o provedor por CAPACIDADE, com fallback
 *     (roteia → Claude Opus 5 → openai);
 *   - `AiExecutionService` — a chamada real, com as chaves vindas do runtime;
 *   - `AiProviderSeeder` — Roteia, Claude Opus 5, OpenAI e HeyGen já semeados.
 *
 *   ⭐ O executor do Escritório NÃO pode, em hipótese alguma, ter a própria
 *   lista de provedores, a própria chave e a própria chamada HTTP. Se tivesse,
 *   teríamos DUAS infraestruturas de IA no mesmo produto: uma para o IA Center,
 *   outra para o Escritório — e elas divergiriam (chave different, modelo
 *   diferente, fallback diferente).
 *
 *   Então a resposta é: o provedor do Escritório DELEGA ao roteamento que já
 *   existe. A interface continua existindo — porque é ela que permite o teste
 *   passar um provedor falso e rodar em milissegundos, sem chamar IA nenhuma.
 *
 * ⭐⭐ E O QUE O EXECUTOR NÃO PODE FAZER:
 *   Ele NÃO registra decisão da VIA, NÃO muda status de tarefa e NÃO publica.
 *   Quem entrega para a supervisão é o MarketingTaskViaGate. É por isso que o
 *   executor não recebe a porta que grava a decisão: mesmo que alguém altere o
 *   código dele, a IA não consegue se autoaprovar.
 */

declare(strict_types=1);

namespace App\Marketing\Domain\Agents;

interface AgentModelProvider
{
    /**
     * O nome do provedor, como aparece no log. Ex.: "roteia", "roteiro".
     */
    public function name(): string;

    /**
     * ⭐ Este provedor consegue rodar este modelo?
     *
     * O Escritório usa isto para recusar com mensagem clara quando o agente pede
     * um modelo que o provedor ativo não tem — em vez de mandar a requisição e
     * receber um 404 difícil de entender.
     */
    public function supports(string $model): bool;

    /**
     * Executa o modelo com o pedido e devolve a resposta.
     *
     * @param  array<string, mixed>  $context  O papel do agente, a etapa e as regras do Escritório.
     */
    public function execute(string $model, string $instruction, array $context): AgentModelResult;
}
