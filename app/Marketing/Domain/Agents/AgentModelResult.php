<?php

/**
 * O QUE É: o resultado de uma execução de modelo — o que o agente produziu.
 * COMO FUNCIONA: um objeto simples com o texto, o modelo que respondeu, os tokens,
 * a latência e o custo.
 * FUNÇÃO NO SISTEMA: carregar a produção do agente com o que a auditoria precisa
 * guardar — inclusive a latência, que é a metade do que se quer avaliar quando
 * se fala em FLUXO (quantas vezes o trabalho passou pela VIA antes de sair).
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
        public readonly int $durationMs = 0,
    ) {
    }
}
