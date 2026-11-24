<?php

/**
 * O QUE É: o resultado de uma execução de modelo — o que o agente produziu.
 * COMO FUNCIONA: um objeto simples com o texto, o modelo que respondeu, os tokens
 * e o custo.
 * FUNÇÃO NO SISTEMA: carregar a produção do agente com o que a auditoria precisa
 * guardar — inclusive o custo, porque o Escritório gerencia agentes que trabalham
 * de verdade e o gestor precisa saber onde o dinheiro está indo.
 *
 * ⭐ POR QUE UM OBJETO E NÃO UM ARRAY: o array volta a perder a forma na primeira
 * função que o recebe, e o `custoCentavos` vira `custo` num lugar e
 * `cost_cents` em outro. Um objeto com nome fixo não tem esse problema.
 */

declare(strict_types=1);

namespace App\Marketing\Domain\Agents;

final class AgentModelResult
{
    public function __construct(
        public readonly string $text,
        public readonly string $modelUsed,
        public readonly int $inputTokens = 0,
        public readonly int $outputTokens = 0,
        public readonly int $costCents = 0,
    ) {
    }
}
