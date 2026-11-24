<?php

/**
 * O QUE É: o contrato de um provedor de modelo de IA para os agentes do Escritório.
 * COMO FUNCIONA: um adaptador para a API de um provedor. Todos os provedors
 * implementam esta interface, então o Escritório não sabe — e não precisa saber —
 * qual IA está rodando.
 * FUNÇÃO NO SISTEMA: ligar os agentes às IAs de verdade sem acoplar o domínio a
 * um fornecedor específico.
 *
 * POR QUE EXISTE COMO INTERFACE (e não como uma classe só):
 *   O mesmo mecanismo das portas de repositório, que o projeto já usa. O serviço
 *   conhece só a interface, então o teste passa uma implementação falsa — sem
 *   chamar IA nenhuma, em milissegundos. E trocar de provedor (RoteirO, Invision,
 *   OpenRouter, Ollama local) é adicionar uma classe, não reescrever o serviço.
 *
 * ⭐⭐ E O QUE O EXECUTOR NÃO PODE FAZER:
 *   Ele NÃO registra decisão da VIA, NÃO muda status de tarefa e NÃO publica.
 *   Quem entrega para a supervisão é o MarketingTaskViaGate. É por isso que o
 *   executor não recebe a porta de decisão: mesmo que alguém altere o código
 *   dele, a IA não consegue se autoaprovar.
 */

declare(strict_types=1);

namespace App\Marketing\Domain\Agents;

interface AgentModelProvider
{
    /**
     * O nome do provedor, como aparece no log. Ex.: "roteiro", "invision", "anthropic".
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
