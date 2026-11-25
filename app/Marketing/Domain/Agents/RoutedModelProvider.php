<?php

/**
 * O QUE É: o provedor do Escritório que ROTEIA entre os provedores da plataforma.
 * COMO FUNCIONA: recebe a lista de provedores que atendem à capacidade pedida e
 * tenta um a um, até um responder.
 * FUNÇÃO NO SISTEMA: ⭐ dar ao Escritório o mesmo fallback que o IA Center já tem —
 * se o RoteirO estiver fora do ar, o trabalho continua no provedor seguinte.
 *
 * ⭐⭐ POR QUE EXISTE, E POR QUE NÃO É UM ROTEADOR NOVO:
 *
 *   ⭐ O `AiRoutingService` JÁ sabe a ordem de preferência por capacidade
 *   (roteia → gemini → openai). Reescrever essa ordem aqui seria uma
 *   segunda fonte de verdade — e as duas divergiriam no dia que alguém ajustasse
 *   uma delas.
 *
 *   ⭐ Então este provedor DELEGA a ordem ao `AiRoutingService` e cuida apenas
 *   do que é específico do Escritório: ⭐ tentar o próximo quando um falha.
 *
 * ⭐⭐ E O QUE A VIA CONTINUA GARANTINDO — mesmo com fallback:
 *
 *   O fallback muda QUAL IA produz. Ele nunca muda QUEM julga. ⭐ A entrega do
 *   agente continua indo para `awaiting_via_review` pelo `MarketingTaskViaGate`,
 *   e nenhuma quantidade de provedores faz a IA se autoaprovar.
 *
 *   ⭐ E MAIS: o modelo pedido é uma PREFERÊNCIA, não uma exigência. Se o agente
 *   pede um modelo que o provedor首选 não tem, o roteamento escolhe o que tem —
 *   porque perder o trabalho é pior que usar outro modelo. O modelo realmente
 *   usado volta na resposta, para o registro mostrar a verdade.
 */

declare(strict_types=1);

namespace App\Marketing\Domain\Agents;

use App\Models\AiProvider;
use App\Services\Ai\AiRoutingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

final class RoutedModelProvider implements AgentModelProvider
{
    /**
     * @param  list<string>  $capabilities  As capacidades que este provedor atende.
     */
    public function __construct(
        private readonly AiRoutingService $routing,
        private readonly array $capabilities = ['copy', 'marketing_strategy', 'critical_review'],
        private readonly int $timeoutSeconds = 120,
    ) {
    }

    public function name(): string
    {
        return 'roteado';
    }

    /**
     * ⭐ O ROTEAMENTO SEMPRE TEM ALGUM PROVEDOR.
     *
     * ⭐ A lista de modelos aceitos NÃO é um filtro aqui: com roteamento, a
     * pergunta "¿este provedor tem este modelo?" é do provedor, não do roteador.
     * O roteador escolhe o provedor pela CAPACIDADE — que é como a plataforma
     * inteira já funciona.
     */
    public function supports(string $model): bool
    {
        return true;
    }

    public function execute(string $model, string $instruction, array $context): AgentModelResult
    {
        $providers = $this->providersFor($context);

        if ($providers === []) {
            throw new RuntimeException(
                'Nenhum provedor de IA está configurado. Defina ROTEIA_BASE_URL, ROTEIA_API_KEY e cadastre os provedores no IA Center.'
            );
        }

        $falhas = [];

        foreach ($providers as $provedor) {
            try {
                $resultado = $this->executeWith($provedor, $model, $instruction, $context);

                $this->registerExecution($provedor, $resultado, $instruction, $context);

                return $resultado;
            } catch (Throwable $e) {
                // ⭐ Um provedor que falha NÃO interrompe o trabalho: o próximo
                // é tentado. É o que dá resiliência de verdade — e o que o
                // `AiRoutingService` faz quando o provedor preferido não está
                // configurado.
                $falhas[] = sprintf('%s: %s', $provedor->slug, $e->getMessage());
            }
        }

        throw new RuntimeException(sprintf(
            'Todos os provedores falharam. %s',
            implode(' | ', $falhas),
        ));
    }

    /**
     * ⭐ A ORDEM DE PREFERÊNCIA.
     *
     * ⭐ Vem do `AiRoutingService` — a mesma ordem que o IA Center usa. Assim o
     * Escritório não tem uma segunda tabela de preferências.
     *
     * @param  array<string, mixed>  $context
     * @return list<AiProvider>
     */
    private function providersFor(array $context): array
    {
        $capability = (string) ($context['capability'] ?? 'copy');

        $route = $this->routing->resolveRoute($capability);
        $slugs = (array) ($route['providers'] ?? []);

        // ⭐⭐ A TABELA DE PROVEDORES PODE NÃO EXISTIR.
        //
        // ⭐ Num ambiente novo (ou no SQLite de teste), a tabela `ai_providers`
        // ainda não foi criada — e a consulta estouraria um erro de banco, que é
        // confuso e não diz nada sobre o que o usuário precisa fazer.
        //
        // ⭐ Com a tabela ausente, o certo é dizer que não há provedor
        // configurado — que é a verdade, e é acionável.
        if (! DB::getSchemaBuilder()->hasTable('ai_providers')) {
            return [];
        }

        $providers = [];

        foreach ($slugs as $slug) {
            $provedor = AiProvider::query()
                ->where('slug', (string) $slug)
                ->where('status', 'ativo')
                ->first();

            if (! $provedor) {
                continue;
            }

            // ⭐⭐ PROVEDORES QUE NÃO FALAM O FORMATO OPENAI FICAM DE FORA.
            //
            // ⭐ O gemini e o heygen têm APIs próprias,
            // com outro corpo e outra resposta. Incluí-los aqui daria uma chamada
            // que volta 404 — e o fallback mascararia um problema de CONFIGURAÇÃO
            // como se fosse o provedor estar fora do ar. ⭐ Um fallback que
            // esconde erro de configuração é pior do que nenhum fallback.
            //
            // ⭐ Chamar esses provedores é trabalho do `AiExecutionService`, que
            // já sabe falar com eles.
            if (! $this->speaksOpenAiProtocol($provedor->slug)) {
                continue;
            }

            // ⭐ O mesmo cuidado do `AiRoutingService`: o gateway só entra na
            // fila quando o runtime está de fato configurado.
            if (
                $provedor->slug === 'roteia'
                && (
                    trim((string) env('ROTEIA_API_KEY', '')) === ''
                    || trim((string) env('ROTEIA_BASE_URL', '')) === ''
                )
            ) {
                continue;
            }

            $providers[] = $provedor;
        }

        return $providers;
    }

    /**
     * ⭐ Este provedor fala o protocolo OpenAI (`/v1/chat/completions`)?
     *
     * ⭐ Os slugs são os de `AiProviderSeeder`: roteia, gemini, openai, heygen.
     */
    private function speaksOpenAiProtocol(string $slug): bool
    {
        return ! in_array(strtolower($slug), ['gemini', 'google-gemini', 'heygen'], true);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function executeWith(
        AiProvider $provedor,
        string $model,
        string $instruction,
        array $context,
    ): AgentModelResult {
        $baseUrl = rtrim($this->baseUrlFor($provedor), '/');
        $apiKey = trim((string) ($provedor->api_key ?? ''));

        if ($apiKey === '') {
            $apiKey = trim((string) env('ROTEIA_API_KEY', ''));
        }

        if ($apiKey === '' || $baseUrl === '') {
            throw new RuntimeException('Provedor sem credencial configurada.');
        }

        $modeloUsado = $this->modelFor($provedor, $model);
        $started = microtime(true);

        $response = \Illuminate\Support\Facades\Http::withToken($apiKey)
            ->acceptJson()
            ->timeout($this->timeoutSeconds)
            ->post($baseUrl.'/v1/chat/completions', [
                'model' => $modeloUsado,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $this->buildPrompt($instruction, $context),
                    ],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException(sprintf(
                'Provedor [%s] respondeu %d.',
                $provedor->slug,
                $response->status(),
            ));
        }

        $payload = $response->json();
        $text = trim((string) ($payload['choices'][0]['message']['content'] ?? ''));

        if ($text === '') {
            throw new RuntimeException(sprintf('Provedor [%s] devolveu resposta vazia.', $provedor->slug));
        }

        return new AgentModelResult(
            text: $text,
            modelUsed: (string) ($payload['model'] ?? $modeloUsado),
            inputTokens: (int) ($payload['usage']['prompt_tokens'] ?? 0),
            outputTokens: (int) ($payload['usage']['completion_tokens'] ?? 0),
            costCents: 0,
            durationMs: (int) round((microtime(true) - $started) * 1000),
        );
    }

    /**
     * ⭐ O MODELO REALMENTE USADO.
     *
     * ⭐ A preferência do agente vence, quando o provedor a aceita. Senão, o
     * provedor decide — porque perder o trabalho é pior que usar outro modelo.
     */
    private function modelFor(AiProvider $provedor, string $modelPreferido): string
    {
        $modelos = (array) data_get($provedor->config, 'models', []);
        $aceitos = array_values(array_map('strval', $modelos));

        if ($aceitos !== [] && in_array($modelPreferido, $aceitos, true)) {
            return $modelPreferido;
        }

        $padrao = data_get($provedor->config, 'model_default');

        if (is_string($padrao) && $padrao !== '') {
            return $padrao;
        }

        return $modelPreferido;
    }

    /**
     * ⭐ A URL BASE DE CADA PROVEDOR.
     *
     * ⭐ O RoteirO vem do ambiente (`ROTEIA_BASE_URL`), que é a mesma variável do
     * IA Center. O OpenAI tem o endpoint padrão do fornecedor, e qualquer um pode
     * ser sobrescrito pelo `config.base_url` do próprio registro do provedor.
     *
     * ⭐ O gemini N
     * `generateContent`, com outro corpo e outra resposta). Incluí-lo aqui
     * daria uma chamada que volta com erro 404 — e o fallback mascararia um
     * problema de configuração como se fosse o provedor estar fora do ar.
     * Tratar o gemini é trabalho do `AiExecutionService`, que já o faz.
     */
    private function baseUrlFor(AiProvider $provedor): string
    {
        $doRegistro = trim((string) data_get($provedor->config, 'base_url', ''));

        if ($doRegistro !== '') {
            return $doRegistro;
        }

        return match ($provedor->slug) {
            'roteia' => trim((string) env('ROTEIA_BASE_URL', '')),
            'openai' => 'https://api.openai.com/v1',
            default => trim((string) env('ROTEIA_BASE_URL', '')),
        };
    }

    /**
     * ⭐⭐ O REGISTRO DA EXECUÇÃO — onde o FLUXO e o CUSTO ficam medidos.
     *
     * ⭐ É a tabela `ai_executions` — a MESMA que o IA Center usa. ⭐ Não criar
     * uma segunda tabela de execuções: duas medições de custo e latência
     * divergem, e o dashboard do IA Center passaria a mostrar um número diferente
     * do real.
     *
     * ⭐ E `estimated_cost` fica em 0 — como em TODO o resto da plataforma —,
     * porque ainda não há tarifário de provedor no produto. ⭐ Quando o tarifário
     * existir, ele é preenchido AQUI e em lugar nenhum mais.
     *
     * @param  array<string, mixed>  $context
     */
    private function registerExecution(
        AiProvider $provedor,
        AgentModelResult $resultado,
        string $instruction,
        array $context,
    ): void {
        try {
            if (! DB::getSchemaBuilder()->hasTable('ai_executions')) {
                return;
            }

            $cols = DB::getSchemaBuilder()->getColumnListing('ai_executions');
            $data = [
                'name' => 'Escritório - '.((string) ($context['agent_name'] ?? 'Agente')),
                'ai_provider_id' => $provedor->id,
                'execution_type' => 'escritorio_virtual',
                'status' => 'Concluído',
                'input' => ['prompt' => $instruction],
                'output' => ['content' => $resultado->text],
                'metadata' => [
                    'agent_name' => $context['agent_name'] ?? null,
                    'department' => $context['department'] ?? null,
                    'task_title' => $context['task_title'] ?? null,
                    'model_used' => $resultado->modelUsed,
                ],
                'tokens_input' => $resultado->inputTokens,
                'tokens_output' => $resultado->outputTokens,
                'duration_seconds' => $resultado->durationMs / 1000,
                'estimated_cost' => 0,
                'started_at' => now(),
                'finished_at' => now(),
            ];

            $safe = array_filter(
                $data,
                fn (string $key): bool => in_array($key, $cols, true),
                ARRAY_FILTER_USE_KEY,
            );

            DB::table('ai_executions')->insert($safe);
        } catch (Throwable) {
            // ⭐ A telemetria NUNCA pode interromper a execução principal. Se o
            // registro falhar, o trabalho do agente foi feito — perder isso
            // seria pior do que perder a métrica.
        }
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
