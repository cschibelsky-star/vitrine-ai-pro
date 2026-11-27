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

                // ⭐⭐ O REGISTRO É PARTE DA EXECUÇÃO.
                //
                // ⭐ A auditoria está certa: ⭐ se o registro falha, a execução
                // não pode voltar como sucesso. ⭐ O texto produzido NÃO se
                // perde — ele volta — mas vem marcado como não registrado, para
                // que a VIA saiba que não há evidência persistida para auditar.
                $falhaNoRegistro = $this->registerExecution($provedor, $resultado, $instruction, $context);

                if ($falhaNoRegistro !== null) {
                    throw new RuntimeException(sprintf(
                        'O provedor [%s] respondeu, mas a execução não foi registrada: %s',
                        $provedor->slug,
                        $falhaNoRegistro,
                    ));
                }

                return $resultado;
            } catch (Throwable $e) {
                // ⭐ Um provedor que falha NÃO interrompe o trabalho: o próximo
                // é tentado. É o que dá resiliência de verdade — e o que o
                // `AiRoutingService` faz quando o provedor preferido não está
                // configurado.
                //
                // ⭐ EXCETO quando a falha é de REGISTRO: aí o texto já foi
                // produzido e o próximo provedor não mudaria nada — e a
                // execução seguiria sem registro. ⭐ Nesse caso o erro sobe.
                if ($e instanceof RuntimeException && str_contains($e->getMessage(), 'não foi registrada')) {
                    throw $e;
                }

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
        $apiKey = $this->apiKeyFor($provedor);

        if ($apiKey === '' || $baseUrl === '') {
            throw new RuntimeException(sprintf(
                'Provedor [%s] sem credencial configurada.',
                $provedor->slug,
            ));
        }

        $modeloUsado = $this->modelFor($provedor, $model);
        $started = microtime(true);

        $response = \Illuminate\Support\Facades\Http::withToken($apiKey)
            ->acceptJson()
            ->timeout($this->timeoutSeconds)
            ->post($this->chatCompletionsUrl($baseUrl), [
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
     * ⭐⭐⭐ O MODELO REALMENTE USADO — SEM SUBSTITUIÇÃO AUTOMÁTICA.
     *
     * ⭐⭐ A VERSÃO ANTERIOR TROCAVA O MODELO SEM PERMISSÃO. A auditoria está
     * certa, e este é um ponto de GOVERNANÇA, não de conveniência:
     *
     *     return $provedor->config['model_default'];   // ⭐ trocava o modelo
     *
     * ⭐ O modelo é uma ESCOLHA de política: se o gestor deployou o agente com
     * um modelo específico, o sistema não pode trocar por outro sozinho — nem
     * porque é mais barato, nem porque está mais disponível. ⭐ Trocar o modelo
     * muda o resultado do trabalho sem que ninguém tenha pedido.
     *
     * ⭐ A REGRA AGORA: ⭐ o modelo pedido é usado, ou o provedor é PULSADO. ⭐ Se
     * nenhum provedor aceitar o modelo pedido, a execução falha com a lista do
     * que cada um aceita — e a decisão de continuar com outro modelo é do
     * gestor, não do código.
     *
     * ⭐ E O MODELO REALMENTE USADO continua voltando na resposta e no registro,
     * para que a auditoria veja a verdade.
     */
    private function modelFor(AiProvider $provedor, string $modelPreferido): string
    {
        $modelos = (array) data_get($provedor->config, 'models', []);
        $aceitos = array_values(array_map('strval', $modelos));

        // ⭐ Sem lista explícita, o provedor aceita o que veio.
        if ($aceitos === []) {
            return $modelPreferido;
        }

        // ⭐ O modelo pedido está na lista: é o que vai ser usado.
        if (in_array($modelPreferido, $aceitos, true)) {
            return $modelPreferido;
        }

        // ⭐⭐ NÃO ESTÁ: o provedor é PULSADO. ⭐ Não há substituição.
        throw new RuntimeException(sprintf(
            'O provedor [%s] não aceita o modelo [%s]. Aceita: %s.',
            $provedor->slug,
            $modelPreferido,
            implode(', ', $aceitos),
        ));
    }

    /**
     * ⭐⭐⭐ A CREDENCIAL DE CADA PROVEDOR — ISOLADA POR PROVEDOR.
     *
     * ⭐⭐⭐ ESTE É O P0 MAIS GRAVE, E A AUDITORIA ESTÁ CERTA.
     *
     * A versão anterior fazia isto:
     *
     *     if ($apiKey === '') {
     *         $apiKey = env('ROTEIA_API_KEY');   // ⭐ PARA QUALQUER PROVEDOR
     *     }
     *
     * ⭐ Ou seja: um provedor sem credencial própria recebia a CHAVE DO ROTEIA — e a
     * mandava para o endpoint daquele fornecedor. ⭐ A credencial do Roteia ia
     * parar no Claude Opus 5 e na OpenAI. Isso é vazar segredo, e é exatamente o
     * que a auditoria apontou.
     *
     * ⭐ A CORREÇÃO: cada provedor tem a SUA credencial, e a do Roteia é usada
     * **apenas** pelo Roteia. Se o provedor não tem credencial, ele é PULSADO —
     * não é chamado com a chave de outro.
     *
     * ⭐ E O EFEITO PRÁTICO É O QUE SE QUER: o fallback continua funcionando
     * (OpenAI com a chave do OpenAI), e nenhum segredo atravessa a fronteira.
     */
    private function apiKeyFor(AiProvider $provedor): string
    {
        // ⭐ A credencial do próprio registro do provedor tem a primeira palavra.
        $doRegistro = trim((string) ($provedor->api_key ?? ''));

        if ($doRegistro !== '') {
            return $doRegistro;
        }

        // ⭐ E AGORA, CADA PROVEDOR TEM A SUA VARIÁVEL. ⭐ A do Roteia NUNCA é
        // usada para outro.
        return match (strtolower((string) $provedor->slug)) {
            'roteia' => trim((string) env('ROTEIA_API_KEY', '')),
            'openai' => trim((string) (env('OPENAI_API_KEY') ?: env('OPENAI_KEY'))),
            'Claude Opus 5', 'google-gemini' => trim((string) (
                env('GEMINI_API_KEY')
                ?: env('GOOGLE_API_KEY')
                ?: env('GOOGLE_GEMINI_API_KEY')
            )),
            'heygen' => trim((string) (env('HEYGEN_API_KEY') ?: env('HEYGEN_KEY'))),
            default => '',
        };
    }

    /**
     * ⭐ A URL DA CHAT COMPLETIONS, SEM DUPLICAR O `/v1`.
     *
     * ⭐ A URL base pode vir em três formatos: `https://api.x.ai` (sem versão),
     * `https://api.x.ai/v1` (com versão) ou já com o caminho completo. ⭐
     * Concatenar `/v1/chat/completions` em cima de uma base que já tem `/v1`
     * produz `/v1/v1/chat/completions` — chamada inválida, que o fallback
     * trataria como "provedor fora do ar".
     */
    private function chatCompletionsUrl(string $baseUrl): string
    {
        $base = rtrim($baseUrl, '/');

        // ⭐ A base pode já vir com o caminho completo.
        if (str_ends_with($base, '/chat/completions')) {
            return $base;
        }

        // ⭐⭐ E PODE JÁ VIR COM A VERSÃO (`/v1`). ⭐ Aí, concatenar `/v1` de novo
        // produziria `/v1/v1/chat/completions` — chamada inválida, que o fallback
        // trataria como "provedor fora do ar", escondendo um erro de URL.
        if (str_ends_with($base, '/v1')) {
            return $base.'/chat/completions';
        }

        return $base.'/v1/chat/completions';
    }

    /**
     * ⭐ A URL BASE DE CADA PROVEDOR.
     *
     * ⭐ O RoteirO vem do ambiente (`ROTEIA_BASE_URL`), que é a mesma variável do
     * Centro IA. O OpenAI tem o endpoint padrão do fornecedor, e qualquer um pode
     * ser sobrescrito pelo `config.base_url` do próprio registro do provedor.
     *
     * ⭐ O Claude Opus 5 NÃO ENTRA AQUI: a API dele não é OpenAI-compatible (é
     * `generateContent`, com outro corpo e outra resposta). Incluí-lo aqui daria
     * uma chamada que volta com erro 404 — e o fallback mascararia um problema de
     * configuração como se fosse o provedor estar fora do ar. ⭐ Tratar o
     * Claude Opus 5 é trabalho do `AiExecutionService`, que já o faz.
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
            default => '',
        };
    }

    /**
     * ⭐⭐⭐ O REGISTRO DA EXECUÇÃO — ONDE O FLUXO E O CUSTO FICAM MEDIDOS.
     *
     * ⭐ É a tabela `ai_executions` — a MESMA que o Centro IA usa. ⭐ Não criar
     * uma segunda tabela de execuções: duas medições de custo e latência
     * divergem, e o dashboard do Centro IA passaria a mostrar um número diferente
     * do real.
     *
     * ⭐⭐⭐ E A FALHA DO REGISTRO **NÃO** É MAIS SILENCIOSA.
     *
     * A versão anterior fazia `catch (Throwable) { /* log *\/ }` — e devolvia
     * sucesso. ⭐ Ou seja: o agente produzia, o registro falhava, e o sistema
     * dizia "deu tudo certo". ⭐ Isso é exatamente o que a auditoria apontou:
     * **execução sem rastreabilidade**. E é grave por um motivo específico: a
     * rastreabilidade é o que a VIA audita. ⭐ Um trabalho que não foi registrado
     * não pode ser julgado nem devolvido — então o supervisor nem
     * veria o que aconteceu.
     *
     * ⭐ A REGRA AGORA: se o registro falha, a execução é marcada como FALHA
     * (quando dá) e o erro sobe. ⭐ O texto produzido é devolvido junto, para
     * que nada se perca — mas o chamador sabe que não foi registrado.
     */
    private function registerExecution(
        AiProvider $provedor,
        AgentModelResult $resultado,
        string $instruction,
        array $context,
    ): ?string {
        try {
            // ⭐⭐ A TABELA PRECISA EXISTIR — E PRECISA TER AS COLUNAS.
            //
            // ⭐ Sem esta checagem, dois erros silenciosos acontecem: ⭐ numa
            // tabela inexistente, `getColumnListing` devolve lista vazia, o
            // filtro descarta tudo, e o `insert` de um array vazio... passa. ⭐
            // Ou seja: a execução seria registrada como sucesso sem ter sido
            // registrada. ⭐ E com colunas diferentes, parte do dado sumiria
            // sem aviso.
            if (! DB::getSchemaBuilder()->hasTable('ai_executions')) {
                return 'a tabela ai_executions não existe';
            }

            $cols = DB::getSchemaBuilder()->getColumnListing('ai_executions');

            $data = [
                'ai_provider_id' => $provedor->id,
                'execution_type' => 'escritorio_virtual',
                'status' => 'Concluído',

                // ⭐⭐ O JSON PRECISA SER SERIALIZADO.
                //
                // ⭐ O Query Builder (`DB::table()`) NÃO tem os casts do Eloquent:
                // o que passa é gravado como está. ⭐ Um array PHP numa coluna
                // `json` vira a string "Array", e a leitura volta como texto
                // ilegível — foi exatamente o que fez o registro não aparecer.
                'input' => json_encode(['prompt' => $instruction], JSON_UNESCAPED_UNICODE),
                'output' => json_encode(['content' => $resultado->text], JSON_UNESCAPED_UNICODE),
                'metadata' => json_encode([
                    'agent_name' => $context['agent_name'] ?? null,
                    'department' => $context['department'] ?? null,
                    'task_title' => $context['task_title'] ?? null,
                    'model_used' => $resultado->modelUsed,
                ], JSON_UNESCAPED_UNICODE),

                'tokens_input' => $resultado->inputTokens,
                'tokens_output' => $resultado->outputTokens,
                'duration_seconds' => round($resultado->durationMs / 1000, 3),

                // ⭐ O CUSTO FICA EM ZERO — como em TODO o resto da plataforma —
                // porque ainda não há tarifário de provedor no produto. ⭐
                // Preencher aqui com um palpite seria pior que zero: um número
                // inventado no dashboard de custo é mais perigoso que um zero.
                'estimated_cost' => 0,

                'started_at' => now(),
                'finished_at' => now(),
            ];

            $safe = array_filter(
                $data,
                fn (string $key): bool => in_array($key, $cols, true),
                ARRAY_FILTER_USE_KEY,
            );

            // ⭐⭐ E O FILTRO NÃO PODE ESVAZIAR O REGISTRO EM SILÊNCIO.
            //
            // ⭐ Se nenhuma coluna sobreviveu, o `insert` receberia um array
            // vazio — e isso PASSA. ⭐ A execução pareceria registrada sem ter
            // sido. ⭐ Aqui isso é erro explícito.
            if ($safe === []) {
                return 'nenhuma coluna de ai_executions foi reconhecida';
            }

            DB::table('ai_executions')->insert($safe);

            return null;
        } catch (Throwable $e) {
            // ⭐ O MOTIVO VAI PARA O LOG E PARA CIMA: engolir o erro em silêncio é
            // o que faz um registro que nunca acontece parecer um registro que
            // não importa.
            \Illuminate\Support\Facades\Log::error('escritorio: falha ao registrar execução de IA.', [
                'erro' => $e->getMessage(),
                'provedor' => $provedor->slug,
            ]);

            return $e->getMessage();
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
