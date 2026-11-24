<?php

/**
 * O QUE É: o provedor de modelo para APIs no formato OpenAI.
 * COMO FUNCIONA: monta o corpo da requisição no formato `/chat/completions` e
 * traduz a resposta.
 * FUNÇÃO NO SISTEMA: ser o ponto onde o Escritório se conecta às IAs de verdade.
 *
 * ⭐ POR QUE UM ÚNICO ADAPTADOR PARA TANTOS PROVEDORES: RoteirO, Invision,
 * OpenRouter, Together, Groq, vLLM local e Ollama (com compatibilidade) falam
 * todos o mesmo formato. Trocar de provedor é trocar a URL base, a chave e a
 * lista de modelos — NADA mais. Um adaptador atende todos.
 *
 * ⭐ E O QUE ESTE ARQUIVO NÃO FAZ: ele não conhece tarefa, não conhece status e
 * não conhece a VIA. Ele produz TEXTO. Quem decide o que acontece com esse texto
 * é o MarketingTaskViaGate.
 */

declare(strict_types=1);

namespace App\Marketing\Domain\Agents;

use Illuminate\Support\Facades\Http;
use RuntimeException;

final class OpenAiCompatibleModelProvider implements AgentModelProvider
{
    /**
     * @param  list<string>  $models  Os modelos que este provedor tem.
     */
    public function __construct(
        private readonly string $name,
        private readonly string $baseUrl,
        private readonly string $apiKey,
        private readonly array $models,
        private readonly int $timeoutSeconds = 120,
    ) {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function supports(string $model): bool
    {
        return in_array($model, $this->models, true);
    }

    public function execute(string $model, string $instruction, array $context): AgentModelResult
    {
        if (! $this->supports($model)) {
            throw new RuntimeException(sprintf(
                'Provedor [%s] não possui o modelo [%s].',
                $this->name,
                $model,
            ));
        }

        $response = Http::withToken($this->apiKey)
            ->acceptJson()
            ->timeout($this->timeoutSeconds)
            ->post(rtrim($this->baseUrl, '/').'/chat/completions', [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $this->buildPrompt($instruction, $context),
                    ],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException(sprintf(
                'Provedor [%s] respondeu %d: %s',
                $this->name,
                $response->status(),
                $this->extractError($response->json()),
            ));
        }

        $payload = $response->json();
        $text = trim((string) ($payload['choices'][0]['message']['content'] ?? ''));

        if ($text === '') {
            throw new RuntimeException(sprintf(
                'Provedor [%s] devolveu uma resposta vazia.',
                $this->name,
            ));
        }

        return new AgentModelResult(
            text: $text,
            modelUsed: (string) ($payload['model'] ?? $model),
            inputTokens: (int) ($payload['usage']['prompt_tokens'] ?? 0),
            outputTokens: (int) ($payload['usage']['completion_tokens'] ?? 0),
            // ⭐ O custo é calculado pelo serviço, não aqui: o preço de cada modelo
            // muda, e um valor fixo neste lugar viraria mentira. O provedor devolve
            // os tokens; a precificação fica com quem conhece a tabela de preços.
            costCents: 0,
        );
    }

    /**
     * ⭐ O PROMPT DO ESCRITÓRIO.
     *
     * É onde a regra da VIA é comunicada ao modelo — para que ele produza o
     * TRABALHO, mas NUNCA altere a decisão da supervisão. Repare no bloco de
     * regras: ele diz explicitamente que o modelo produz material para a VIA
     * julgar, e que não decide nada sobre a supervisão.
     *
     * Por que o prompt é montado aqui, e não no serviço? Porque é o PROVEDOR que
     * sabe o formato da API — e o formato do prompt é parte do formato da API.
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
        $sections[] = (string) ($context['rules'] ?? self::defaultRules());

        return implode("\n", $sections);
    }

    /**
     * ⭐ AS REGRAS QUE TODO AGENTE DO ESCRITÓRIO RECEBE.
     *
     * ⭐⭐ O TRECHO IMPORTANTE É O ÚLTIMO: "você não aprova nada". O modelo produz
     * material; a VIA julga. É o que garante que uma IA com acesso ao código do
     * Escritório não consiga se autoaprovar.
     */
    public static function defaultRules(): string
    {
        return implode("\n", [
            '1. Você produz o TRABALHO. Você não decide se o trabalho está bom.',
            '2. Você não aprova, não publica e não passa tarefa para outro agente.',
            '3. A supervisão (VIA) é feita por fora de você. Sua entrega será revisada.',
            '4. Se a VIA reprovar sua entrega, ela volta para VOCÊ — não para outro agente.',
            '5. Entregue o resultado pronto para revisão: explique o que fez e como testar.',
        ]);
    }

    /** @param mixed $json */
    private function extractError(mixed $json): string
    {
        if (is_array($json) && isset($json['error']['message'])) {
            return (string) $json['error']['message'];
        }

        return 'sem detalhes';
    }
}
