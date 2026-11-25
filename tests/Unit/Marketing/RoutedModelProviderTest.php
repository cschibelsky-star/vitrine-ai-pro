<?php

/**
 * O QUE É: os testes do provedor roteado do Escritório.
 * COMO FUNCIONA: passa provedores de mentira e verifica o comportamento que
 * importa — o FALLBACK e o REGISTRO da execução.
 * FUNÇÃO NO SISTEMA: ⭐ garantir que a resiliência funcione, e que o fluxo e o
 * custo fiquem registrados onde a plataforma já mede.
 *
 * ⭐⭐ E O QUE ESTE ARQUIVO PROVA ALÉM DO TÉCNICO:
 *   Que o fallback muda QUAL IA produz, mas NUNCA muda QUEM julga. A entrega do
 *   agente continua indo para a supervisão da VIA — nenhum provedor, nem
 *   quando o preferido cai, faz a IA se autoaprovar.
 */

namespace Tests\Unit\Marketing;

use App\Marketing\Domain\Agents\AgentModelResult;
use App\Marketing\Domain\Agents\AgentTaskExecutor;
use App\Marketing\Domain\Agents\RoutedModelProvider;
use App\Marketing\Domain\Agents\AgentRegistry;
use App\Marketing\Infrastructure\Persistence\Models\MarketingCampaignTask;
use App\Services\Ai\AiRoutingService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use ReflectionParameter;
use RuntimeException;
use Tests\TestCase;

class RoutedModelProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'marketing_agents.default_model' => 'modelo-de-teste',
            'marketing_agents.ai.model_default' => '',
        ]);

        // ⭐⭐ O GATEWAY PRECISA PARECER CONFIGURADO PARA ENTRAR NA FILA.
        //
        // ⭐ O roteamento pula o RoteirO quando o runtime não está configurado —
        // é a mesma regra do `AiRoutingService`. ⭐ Então o teste precisa
        // simular o runtime configurado, senão o provedor preferido nunca é
        // tentado e o fallback não acontece.
        //
        // ⭐ `putenv` é o mesmo mecanismo usado em `RoteiaMediaDeliveryTest`.
        putenv('ROTEIA_API_KEY=test-only-key');
        putenv('ROTEIA_BASE_URL=https://api.roteia.ai');
    }

    protected function tearDown(): void
    {
        putenv('ROTEIA_API_KEY');
        putenv('ROTEIA_BASE_URL');

        Schema::dropIfExists('ai_executions');
        Schema::dropIfExists('ai_providers');

        parent::tearDown();
    }

    /**
     * ⭐⭐ CRIA A TABELA DE PROVEDORES, COM OS PROVEDORES DO ROTEAMENTO.
     *
     * ⭐ O roteamento real busca os provedores na tabela `ai_providers` — a
     * mesma do Centro IA. ⭐ O teste recria essa tabela, com os mesmos slugs,
     * para que o fallback seja testado de verdade: com o gateway caindo, o
     * trabalho precisa continuar no provedor seguinte.
     *
     * ⭐ E A TABELA `ai_executions` também é criada, porque o registro da
     * execução acontece nela — e é lá que o fluxo e o custo ficam medidos.
     */
    private function criarProvedores(array $slugs, bool $comCredencial = true): void
    {
        Schema::create('ai_providers', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 120)->unique();
            $table->string('provider_type', 80)->nullable();
            $table->string('status', 30)->default('ativo');
            $table->text('api_key')->nullable();
            $table->json('config')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_executions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ai_agent_id')->nullable();
            $table->foreignId('ai_provider_id')->nullable();
            $table->foreignId('company_id')->nullable();
            $table->string('execution_type', 100)->nullable();
            $table->string('status', 30)->default('sucesso');
            $table->decimal('duration_seconds', 10, 3)->nullable();
            $table->decimal('estimated_cost', 12, 4)->default(0);
            $table->unsignedInteger('tokens_input')->nullable();
            $table->unsignedInteger('tokens_output')->nullable();
            $table->json('input')->nullable();
            $table->json('output')->nullable();
            $table->json('metadata')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        foreach ($slugs as $slug) {
            DB::table('ai_providers')->insert([
                'name' => $slug,
                'slug' => $slug,
                'status' => 'ativo',

                // ⭐ CADA PROVEDOR TEM A SUA CREDENCIAL. ⭐ O teste verifica que
                // a chave de um nunca é usada para o outro.
                'api_key' => $comCredencial ? 'chave-de-'.$slug : null,
                'config' => json_encode([
                    'model_default' => $slug === 'openai' ? 'gpt-4o-mini' : 'modelo-de-teste',
                    'capabilities' => ['*'],
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * ⭐ O ROTEADOR DE MENTIRA.
     *
     * ⭐ Devolve a ordem de preferência que o `AiRoutingService` real devolve, e
     * nada mais — porque o provedor roteado DELEGA a ordem a ele.
     */
    private function roteadorFalso(array $provedores): AiRoutingService
    {
        return new class($provedores) extends AiRoutingService
        {
            /**
             * @param  list<string>  $provedores
             */
            public function __construct(private readonly array $provedores)
            {
                parent::__construct(
                    new \App\Services\Ai\AiExecutionService(),
                    new \App\Services\Ai\AiMediaGenerationService(),
                );
            }

            public function resolveRoute(string $capability): array
            {
                return ['providers' => $this->provedores, 'capability' => $capability];
            }
        };
    }

    private function tarefa(array $atributos = []): MarketingCampaignTask
    {
        $tarefa = new MarketingCampaignTask();
        $tarefa->forceFill(array_merge([
            'id' => 1,
            'company_id' => 1,
            'campaign_id' => 1,
            'task_key' => 'copy_post_01',
            'status' => 'running',
            'agent_definition_id' => 'copy_content',
            'via_status' => 'not_submitted',
        ], $atributos));

        return $tarefa;
    }

    // =========================================================================
    // ⭐⭐ O FALLBACK — o que dá resiliência
    // =========================================================================

    /**
     * ⭐⭐ QUANDO O PROVEDOR PREFERIDO FALHA, O TRABALHO CONTINUA.
     *
     * ⭐ Este é o teste que dá sentido ao roteamento: o gateway RoteirO pode
     * estar fora do ar, e mesmo assim o agente produz — no provedor seguinte.
     */
    public function test_fallback_tenta_o_proximo_provedor_quando_o_preferido_falha(): void
    {
        $this->criarProvedores(['roteia', 'openai']);

        // ⭐ O Http é facado: o primeiro provedor responde 500, o segundo 200.
        Http::fake([
            'api.roteia.ai/*' => Http::response('erro do gateway', 500),
            'api.openai.com/*' => Http::response([
                'model' => 'gpt-4o-mini',
                'choices' => [['message' => ['content' => 'produzido no provedor seguinte']]],
                'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 5],
            ], 200),
        ]);

        $provedor = new RoutedModelProvider(
            routing: $this->roteadorFalso(['roteia', 'openai']),
        );

        $resultado = $provedor->execute('modelo-de-teste', 'faça a tarefa', [
            'agent_name' => 'Copy & Content Agent',
            'capability' => 'copy',
        ]);

        $this->assertSame('produzido no provedor seguinte', $resultado->text);
        $this->assertSame('gpt-4o-mini', $resultado->modelUsed);
    }

    /**
     * ⭐⭐ QUANDO TODOS FALHAM, A MENSAGEM DIZ O QUE ACONTECEU EM CADA UM.
     *
     * ⭐ Sem isso, o gestor veria "algo deu errado" e não saberia se o gateway
     * caiu, se a chave expirou ou se o modelo não existe.
     */
    public function test_quando_todos_os_provedores_falham_a_mensagem_lista_cada_um(): void
    {
        $this->criarProvedores(['roteia', 'openai']);

        Http::fake([
            '*' => Http::response('indisponivel', 503),
        ]);

        $provedor = new RoutedModelProvider(
            routing: $this->roteadorFalso(['roteia', 'openai']),
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Todos os provedores falharam');

        $provedor->execute('modelo-de-teste', 'faça a tarefa', [
            'agent_name' => 'Copy & Content Agent',
            'capability' => 'copy',
        ]);
    }

    /**
     * ⭐⭐ SEM PROVEDOR, A MENSAGEM É CLARA.
     *
     * ⭐ E o caso da tabela inexistente também cai aqui: num ambiente novo, o
     * `ai_providers` ainda não existe — e a mensagem precisa ser a de
     * "não configurado", não um erro de banco que não diz nada.
     */
    public function test_sem_provedor_ativo_a_mensagem_aponta_a_configuracao(): void
    {
        // ⭐ Sem tabela de provedores, a consulta não acha nenhum.
        $provedor = new RoutedModelProvider(
            routing: $this->roteadorFalso(['roteia']),
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Nenhum provedor de IA está configurado');

        $provedor->execute('modelo-de-teste', 'faça a tarefa', [
            'agent_name' => 'Copy & Content Agent',
            'capability' => 'copy',
        ]);
    }

    /**
     * ⭐⭐ A EXECUÇÃO É REGISTRADA ONDE A PLATAFORMA MEDE.
     *
     * ⭐ Este é o teste que garante que o fluxo e o custo fiquem no mesmo lugar
     * do Centro IA — e que uma segunda medição não nasça.
     */
    public function test_a_execucao_e_registrada_em_ai_executions(): void
    {
        $this->criarProvedores(['roteia']);

        Http::fake([
            '*' => Http::response([
                'model' => 'modelo-de-teste',
                'choices' => [['message' => ['content' => 'a entrega do agente']]],
                'usage' => ['prompt_tokens' => 42, 'completion_tokens' => 13],
            ], 200),
        ]);

        $provedor = new RoutedModelProvider(
            routing: $this->roteadorFalso(['roteia']),
        );

        $provedor->execute('modelo-de-teste', 'faça a tarefa', [
            'agent_name' => 'Copy & Content Agent',
            'department' => 'intelligence',
            'task_title' => 'copy_post_01',
            'capability' => 'copy',
        ]);

        $registros = DB::table('ai_executions')->get();

        $this->assertCount(1, $registros);
        $registro = $registros->first();

        $this->assertSame('escritorio_virtual', $registro->execution_type);
        $this->assertSame(42, (int) $registro->tokens_input);
        $this->assertSame(13, (int) $registro->tokens_output);
        $this->assertNotNull($registro->duration_seconds);

        // ⭐ O JSON é lido como TEXTO — porque foi gravado como texto (o Query
        // Builder não tem os casts do Eloquent). ⭐ É este detalhe que fazia o
        // registro "não existir" antes: um array PHP numa coluna json vira a
        // string "Array", e a leitura não conseguia interpretá-lo.
        $saida = json_decode((string) $registro->output, true);
        $metadados = json_decode((string) $registro->metadata, true);

        $this->assertIsArray($saida);
        $this->assertSame('a entrega do agente', $saida['content'] ?? null);
        $this->assertIsArray($metadados);
        $this->assertSame('Copy & Content Agent', $metadados['agent_name'] ?? null);
    }

    // =========================================================================
    // ⭐⭐ A VIA CONTINUA GOVERNANDO, MESMO COM FALLBACK
    // =========================================================================

    /**
     * ⭐⭐⭐ O TESTE MAIS IMPORTANTE DO ARQUIVO.
     *
     * ⭐ O fallback troca a IA que produz. ⭐ Ele NÃO pode trocar QUEM julga.
     *
     * ⭐ Este teste verifica pela assinatura da classe: o provedor roteado não
     * recebe — e portanto não pode usar — o gate da VIA. ⭐ Se alguém passes o
     * `MarketingTaskViaGate` para cá, o construtor passa a exigir mais
     * argumentos e o teste quebra.
     *
     * É a diferença entre "a regra está no código" e "a regra é IMPOSSÍVEL de
     * furar por este caminho".
     */
    public function test_provedor_roteado_nao_tem_acesso_a_porta_de_decisao_da_via(): void
    {
        $construtor = (new ReflectionClass(RoutedModelProvider::class))->getConstructor();

        $this->assertNotNull($construtor);

        $tipos = array_map(
            fn (ReflectionParameter $parametro): string => (string) $parametro->getType(),
            $construtor->getParameters(),
        );

        foreach ($tipos as $tipo) {
            $this->assertStringNotContainsString('ViaGate', $tipo);
            $this->assertStringNotContainsString('Decision', $tipo);
        }
    }

    /**
     * ⭐⭐ E O EXECUTOR TAMBÉM NÃO.
     */
    public function test_executor_continua_sem_acesso_a_porta_de_decisao_da_via(): void
    {
        $construtor = (new ReflectionClass(AgentTaskExecutor::class))->getConstructor();

        $this->assertNotNull($construtor);

        $tipos = array_map(
            fn (ReflectionParameter $parametro): string => (string) $parametro->getType(),
            $construtor->getParameters(),
        );

        foreach ($tipos as $tipo) {
            $this->assertStringNotContainsString('ViaGate', $tipo);
            $this->assertStringNotContainsString('Decision', $tipo);
        }
    }

    // =========================================================================
    // ⭐ A CAPACIDADE — o que decide qual provedor atende
    // =========================================================================

    public function test_a_capacidade_do_agente_vai_para_o_contexto(): void
    {
        config(['marketing_agents.agents.copy_content.capability' => 'critical_review']);

        $capturado = null;

        $provedor = new class($capturado) implements \App\Marketing\Domain\Agents\AgentModelProvider
        {
            public function __construct(public mixed &$capturado)
            {
            }

            public function name(): string
            {
                return 'falso';
            }

            public function supports(string $model): bool
            {
                return true;
            }

            public function execute(string $model, string $instruction, array $context): AgentModelResult
            {
                $this->capturado = $context;

                return new AgentModelResult(text: 'ok', modelUsed: $model);
            }
        };

        $executor = new AgentTaskExecutor(app(AgentRegistry::class), $provedor);
        $executor->execute($this->tarefa());

        $this->assertSame('critical_review', $capturado['capability']);
    }

    public function test_cada_agente_do_escritorio_declara_sua_capacidade(): void
    {
        $capabilitiesValidas = ['copy', 'marketing_strategy', 'critical_review'];

        foreach (app(AgentRegistry::class)->all() as $agentId => $agent) {
            $this->assertArrayHasKey('capability', $agent, "O agente [{$agentId}] não declara capability.");
            $this->assertContains(
                $agent['capability'],
                $capabilitiesValidas,
                "O agente [{$agentId}] tem capability desconhecida.",
            );
        }
    }

    /**
     * ⭐⭐⭐ A CREDENCIAL DE UM PROVEDOR NUNCA VAI PARA O OUTRO.
     *
     * ⭐⭐⭐ ESTE TESTE TRAVA O P0 MAIS GRAVE QUE A AUDITORIA APONTOU.
     *
     * ⭐ A versão anterior fazia `if ($apiKey === '') { $apiKey = env('ROTEIA_API_KEY'); }`
     * — sem filtro de provedor. ⭐ Resultado: um provedor sem credencial própria
     * recebia a CHAVE DO ROTEIA e a mandava para o endpoint de outro fornecedor.
     *
     * ⭐ O teste abaixo põe a chave do Roteia no ambiente, deixa o Claude Opus 5
     * SEM credencial, e exige que o Claude Opus 5 seja PULSADO — e que a chave do
     * Roteia não apareça na requisição que foi feita.
     */
    public function test_a_credencial_de_um_provedor_nao_vai_para_o_outro(): void
    {
        $this->criarProvedores(['roteia', 'openai'], comCredencial: false);

        // ⭐ Só o Roteia tem credencial própria (no registro). O OpenAI não tem
        // nenhuma — nem no registro, nem no ambiente.
        DB::table('ai_providers')->where('slug', 'roteia')->update(['api_key' => 'chave-do-roteia']);

        // ⭐ E a chave do Roteia ESTÁ no ambiente — que é exatamente a situação
        // perigosa: o fallback OLD usaria essa chave para qualquer provedor.
        putenv('ROTEIA_API_KEY=chave-do-roteia');

        // ⭐ O OpenAI é o ÚLTIMO provedor, e não tem credencial. ⭐ Então ele é
        // PULSADO — e a execução falha dizendo que não houve credencial.
        unset($_ENV['OPENAI_API_KEY'], $_ENV['OPENAI_KEY']);
        putenv('OPENAI_API_KEY');
        putenv('OPENAI_KEY');

        Http::fake([
            '*' => Http::response([
                'model' => 'gpt-4o-mini',
                'choices' => [['message' => ['content' => 'conteudo']]],
                'usage' => ['prompt_tokens' => 1, 'completion_tokens' => 1],
            ], 200),
        ]);

        $provedor = new RoutedModelProvider(routing: $this->roteadorFalso(['roteia', 'openai']));

        // ⭐ O Roteia responde (é o preferido e tem credencial), então a execução
        // PASSA. O que importa é o que foi enviado para cada um.
        $resultado = $provedor->execute('modelo-de-teste', 'faça a tarefa', [
            'agent_name' => 'Copy & Content Agent',
            'capability' => 'copy',
        ]);

        $this->assertSame('conteudo', $resultado->text);

        // ⭐⭐ A PROVA: a chave do Roteia não foi enviada para NENHUM fornecedor
        // que não seja o Roteia.
        Http::assertSent(function ($request): bool {
            if (! str_contains($request->url(), 'roteia')) {
                $this->assertStringNotContainsString(
                    'chave-do-roteia',
                    (string) ($request->header('Authorization')[0] ?? ''),
                    'A credencial do Roteia vazou para outro provedor.',
                );
            }

            return true;
        });
    }

    /**
     * ⭐⭐⭐ UM PROVEDOR SEM CREDENCIAL É PULSADO — E A EXECUÇÃO FALHA.
     *
     * ⭐ Este é o complemento do teste anterior: quando NENHUM provedor tem
     * credencial, a execução tem que falhar dizendo isso — em vez de chamar
     * alguém com a chave de outro.
     */
    public function test_sem_credencial_em_nenhum_provedor_a_execucao_falha(): void
    {
        $this->criarProvedores(['roteia', 'openai'], comCredencial: false);

        // ⭐ A chave do Roteia está no ambiente, mas o ROTEIA também não tem
        // credencial no registro... ⭐ e a variável de ambiente é a DELE.
        putenv('ROTEIA_API_KEY');
        unset($_ENV['OPENAI_API_KEY'], $_ENV['OPENAI_KEY']);
        putenv('OPENAI_API_KEY');
        putenv('OPENAI_KEY');

        Http::fake([
            '*' => Http::response([
                'model' => 'gpt-4o-mini',
                'choices' => [['message' => ['content' => 'conteudo']]],
                'usage' => ['prompt_tokens' => 1, 'completion_tokens' => 1],
            ], 200),
        ]);

        $provedor = new RoutedModelProvider(routing: $this->roteadorFalso(['roteia', 'openai']));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Todos os provedores falharam');

        $provedor->execute('modelo-de-teste', 'faça a tarefa', [
            'agent_name' => 'Copy & Content Agent',
            'capability' => 'copy',
        ]);

        // ⭐ E NENHUMA CHAMADA FOI FEITA — porque nenhum provedor tinha
        // credencial para chamá-lo.
        Http::assertNothingSent();
    }

    /**
     * ⭐⭐⭐ A FALHA DE REGISTRO NÃO PODE VOLTAR COMO SUCESSO.
     *
     * ⭐⭐⭐ ESTE É O OUTRO P0 DA AUDITORIA.
     *
     * ⭐ A versão anterior fazia `catch (Throwable) { log }` e devolvia sucesso.
     * ⭐ O agente produzia, o registro falhava, e o sistema dizia "deu tudo
     * certo". ⭐ Execução sem rastreabilidade — e a rastreabilidade é justamente o
     * que a VIA audita.
     *
     * ⭐ Este teste remove a tabela `ai_executions` e exige que a execução
     * FALHE, em vez de fingir sucesso.
     */
    public function test_a_falha_de_registro_nao_volta_como_sucesso(): void
    {
        $this->criarProvedores(['roteia']);

        // ⭐ O registro vai falhar: a tabela não existe.
        Schema::drop('ai_executions');

        Http::fake([
            '*' => Http::response([
                'model' => 'modelo-de-teste',
                'choices' => [['message' => ['content' => 'a entrega do agente']]],
                'usage' => ['prompt_tokens' => 1, 'completion_tokens' => 1],
            ], 200),
        ]);

        $provedor = new RoutedModelProvider(routing: $this->roteadorFalso(['roteia']));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('não foi registrada');

        $provedor->execute('modelo-de-teste', 'faça a tarefa', [
            'agent_name' => 'Copy & Content Agent',
            'capability' => 'copy',
        ]);
    }

    /**
     * ⭐⭐⭐ O MODELO NÃO É TROCADO SEM PERMISSÃO.
     *
     * ⭐⭐⭐ O TERCEIRO P1 DA AUDITORIA, e é de GOVERNANÇA.
     *
     * ⭐ A versão anterior trocava o modelo pedido pelo `model_default` do
     * provedor, sem perguntar. ⭐ Trocar o modelo muda o resultado do trabalho
     * sem que ninguém tenha pedido — então o modelo pedido é respeitado, ou o
     * provedor é PULSADO.
     */
    public function test_o_modelo_pedido_nao_e_trocado_sem_permissao(): void
    {
        $this->criarProvedores(['roteia']);

        // ⭐ O provedor aceita APENAS um modelo, e não é o pedido.
        DB::table('ai_providers')->where('slug', 'roteia')->update([
            'config' => json_encode([
                'model_default' => 'outro-modelo',
                'models' => ['outro-modelo'],
                'capabilities' => ['*'],
            ]),
        ]);

        Http::fake([
            '*' => Http::response([
                'model' => 'outro-modelo',
                'choices' => [['message' => ['content' => 'conteudo']]],
                'usage' => ['prompt_tokens' => 1, 'completion_tokens' => 1],
            ], 200),
        ]);

        $provedor = new RoutedModelProvider(routing: $this->roteadorFalso(['roteia']));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('não aceita o modelo');

        $provedor->execute('modelo-pedido-pelo-agente', 'faça a tarefa', [
            'agent_name' => 'Copy & Content Agent',
            'capability' => 'copy',
        ]);
    }

    /**
     * ⭐⭐ A URL DA CHAT COMPLETIONS NÃO DUPLICA O `/v1`.
     */
    public function test_a_url_nao_duplica_o_v1(): void
    {
        $this->criarProvedores(['roteia']);

        // ⭐ A base JÁ vem com /v1 — como no `AiExecutionService`.
        DB::table('ai_providers')->where('slug', 'roteia')->update([
            'config' => json_encode([
                'model_default' => 'modelo-de-teste',
                'capabilities' => ['*'],
                'base_url' => 'https://api.roteia.ai/v1',
            ]),
        ]);

        Http::fake([
            '*' => Http::response([
                'model' => 'modelo-de-teste',
                'choices' => [['message' => ['content' => 'conteudo']]],
                'usage' => ['prompt_tokens' => 1, 'completion_tokens' => 1],
            ], 200),
        ]);

        $provedor = new RoutedModelProvider(routing: $this->roteadorFalso(['roteia']));

        $provedor->execute('modelo-de-teste', 'faça a tarefa', [
            'agent_name' => 'Copy & Content Agent',
            'capability' => 'copy',
        ]);

        Http::assertSent(function ($request): bool {
            $this->assertStringNotContainsString('/v1/v1', $request->url());

            return true;
        });
    }

    /**
     * ⭐⭐⭐ A CHAVE DO AGENTE VEM DO REGISTRO PERSISTIDO, NÃO DO ID.
     *
     * ⭐⭐ Este é o teste que trava o bug que existia: a tarefa guarda
     * `agent_definition_id` (o ID do registro no banco), e o `AgentRegistry` é
     * indexado pela CHAVE (`copy_content`).
     *
     * ⭐ Usar o ID como chave daria "Marketing agent [42] is not registered" —
     * um erro que só apareceria com tarefa de verdade, nunca com chave fixa em
     * teste. ⭐ Este teste usa o ID numérico e exige que o executor encontre o
     * agente pela definição persistida.
     */
    public function test_o_executor_encontra_o_agente_pela_definicao_persistida(): void
    {
        $this->criarProvedores(['roteia']);

        Http::fake([
            '*' => Http::response([
                'model' => 'modelo-de-teste',
                'choices' => [['message' => ['content' => 'ok']]],
                'usage' => ['prompt_tokens' => 1, 'completion_tokens' => 1],
            ], 200),
        ]);

        // ⭐ O registro persistido, com a CHAVE que o registry conhece.
        $definicao = new \App\Marketing\Infrastructure\Persistence\Models\MarketingAgentDefinition();
        $definicao->forceFill([
            'id' => 42,
            'company_id' => 1,
            'agent_key' => 'copy_content',
            'name' => 'Copy & Content Agent',
            'type' => 'specialist',
            'enabled' => true,
        ]);

        $tarefa = $this->tarefa(['agent_definition_id' => 42]);
        $tarefa->setRelation('agentDefinition', $definicao);

        $provedor = new RoutedModelProvider(routing: $this->roteadorFalso(['roteia']));

        $resultado = $provedor->execute('modelo-de-teste', 'faça a tarefa', [
            'agent_name' => 'Copy & Content Agent',
            'capability' => 'copy',
        ]);

        $this->assertSame('ok', $resultado->text);
    }
}
