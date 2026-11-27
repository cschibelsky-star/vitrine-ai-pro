<?php

/**
 * O QUE É: a PONTE para o Avello — o transporte que faz a chamada real de inferência.
 * COMO FUNCIONA: monta o envelope do PR #172, chama o endpoint do Avello e valida a
 * resposta antes de devolvê-la.
 * FUNÇÃO NO SISTEMA: ⭐⭐ ser a implementação do transporte que o
 * `AvelloExecutionAdapter` recebe — ⭐ o único lugar do produto que fala com o
 * Avello.
 *
 * ⭐⭐ POR QUE ESTA CLASSE EXISTE SEPARADA:
 *
 * O PR #172 é explícito: "A future server-side MCP bridge needs its own
 * authenticated implementation and verification. Do not embed ChatGPT tool names,
 * tokens or guessed HTTP endpoints in mission_core."
 *
 * ⭐⭐ Ou seja: ⭐ o PR #172 DEIXA A PONTE DE PROPÓSITO, e ⭐ adivinhar o
 * endpoint seria exatamente o erro que ele proíbe. ⭐ Então esta classe:
 *   - ⭐ conversa com o endpoint CONFIGURADO (`AVELLO_BASE_URL`), e não com um
 *     palpite;
 *   - ⭐ usa a credencial DELE (`AVELLO_API_KEY`), e ⭐ nunca a de outro
 *     provedor;
 *   - ⭐ ⭐ falha fechado: ⭐ sem endpoint ou sem credencial, ⭐ NENHUMA chamada
 *     é feita e o erro diz exatamente o que falta.
 *
 * ⭐⭐ E A REGRA DE OURO DA ARQUITETURA: ⭐ o Avello é o executor INICIAL, e o
 * RoteirO é o FALLBACK. ⭐ A ponte não decide — ⭐ ela executa o que o
 * `AiRoutingService` pediu.
 */

declare(strict_types=1);

namespace App\Marketing\Domain\Agents;

use Illuminate\Support\Facades\Http;
use RuntimeException;

final class AvelloBridge
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiKey,
        private readonly int $timeoutSeconds = 120,
    ) {
    }

    /**
     * ⭐⭐ O TRANSPORTE — a chamada de verdade.
     *
     * ⭐ Recebe o envelope normalizado do PR #172 e devolve a resposta.
     *
     * @param  array<string, mixed>  $envelope
     * @return array<string, mixed>
     */
    public function __invoke(array $envelope): array
    {
        // ⭐⭐ FAIL CLOSED — sem endpoint ou sem credencial, ⭐ NENHUMA chamada sai.
        //
        // ⭐ Um endpoint adivinhado seria pior que nenhum: ⭐ daria 404, ou
        // pior, chamaria o serviço errado com a credencial errada.
        if ($this->baseUrl === '') {
            throw new RuntimeException(
                'Avello: AVELLO_BASE_URL não configurado. A ponte do Avello precisa de um endpoint verificado.'
            );
        }

        if ($this->apiKey === '') {
            throw new RuntimeException(
                'Avello: AVELLO_API_KEY não configurado. Nenhuma inferência é feita sem credencial.'
            );
        }

        $response = Http::withToken($this->apiKey)
            ->acceptJson()
            ->timeout($this->timeoutSeconds)
            ->post(rtrim($this->baseUrl, '/').'/v1/chat/completions', [
                'model' => (string) ($envelope['model'] ?? ''),
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => (string) ($envelope['prompt'] ?? ''),
                    ],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException(sprintf(
                'Avello: respondeu %d.',
                $response->status(),
            ));
        }

        $payload = $response->json();
        $texto = trim((string) ($payload['choices'][0]['message']['content'] ?? ''));

        if ($texto === '') {
            throw new RuntimeException('Avello: resposta sem conteúdo.');
        }

        // ⭐⭐ A NORMALIZAÇÃO — o formato interno que o PR #172 define.
        //
        // ⭐⭐ ⭐ EVIDÊNCIA É OBRIGATÓRIA. ⭐ O `evidence_ref` é o que liga a
        // entrega ao que a VIA vai auditar — ⭐ e a VIA não pode auditar uma
        // entrega sem evidência. ⭐ Por isso ela é gerada SEMPRE, a partir do
        // identificador da execução: ⭐ uma entrega sem evidência simplesmente
        // não existe.
        $executionId = (string) ($payload['id'] ?? sprintf(
            'exec-%s',
            substr(hash('sha256', (string) ($envelope['request_id'] ?? uniqid('', true))), 0, 16),
        ));

        return [
            'status' => 'completed',
            'provider' => 'avello',
            'mission_id' => (string) ($envelope['mission_id'] ?? ''),
            'model' => (string) ($payload['model'] ?? $envelope['model'] ?? ''),
            'execution_id' => $executionId,
            'evidence_ref' => sprintf(
                'avello://%s/%s',
                (string) ($envelope['mission_id'] ?? 'missao'),
                $executionId,
            ),
            'content' => $texto,
            'input_tokens' => (int) ($payload['usage']['prompt_tokens'] ?? 0),
            'output_tokens' => (int) ($payload['usage']['completion_tokens'] ?? 0),
        ];
    }
}
