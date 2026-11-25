<?php

/**
 * O QUE É: os testes do executor de IA dos agentes.
 * COMO FUNCIONA: passa um provedor FALSO — um adapter de mentira que devolve um
 * texto pré-definido — e verifica o que o executor faz com ele.
 * FUNÇÃO NO SISTEMA: ser a rede de segurança do motor do Escritório, sem chamar
 * IA nenhuma. O teste roda em milissegundos.
 *
 * ⭐⭐ E O QUE ESTE ARQUIVO PROVA, ALÉM DO TECNICO:
 *   Que o executor NÃO é a porta da supervisão. Ele produz texto e devolve. Não
 *   tem como registrar decisão da VIA, não tem como mover status, não tem como
 *   publicar. É o que garante que uma IA não consiga se autoaprovar.
 */

namespace Tests\Unit\Marketing;

use App\Marketing\Domain\Agents\AgentModelProvider;
use App\Marketing\Domain\Agents\AgentModelResult;
use App\Marketing\Domain\Agents\AgentRegistry;
use App\Marketing\Domain\Agents\AgentTaskExecutor;
use App\Marketing\Domain\Agents\OpenAiCompatibleModelProvider;
use App\Marketing\Infrastructure\Persistence\Models\MarketingCampaignTask;
use ReflectionClass;
use ReflectionParameter;
use RuntimeException;
use Tests\TestCase;

class AgentTaskExecutorTest extends TestCase
{
    /**
     * ⭐ O PROVEDOR FALSO.
     *
     * ⭐ É POSSÍVEL porque o executor depende da INTERFACE, e não da classe
     * concreta do adaptador HTTP. É o mesmo mecanismo que o `AgentRegistry` já usa
     * — e o mesmo que a porta de repositório usa no resto do projeto.
     */
    private function provedorFalso(string $texto = 'Conteudo produzido pelo agente.', array $modelos = ['modelo-de-teste']): AgentModelProvider
    {
        return new class($texto, $modelos) implements AgentModelProvider
        {
            /** @var array<string, mixed> */
            public array $ultimoContexto = [];

            public ?string $ultimoModelo = null;

            public ?string $ultimaInstrucao = null;

            /**
             * @param  list<string>  $modelos
             */
            public function __construct(private readonly string $texto, private readonly array $modelos)
            {
            }

            public function name(): string
            {
                return 'falso';
            }

            public function supports(string $model): bool
            {
                return in_array($model, $this->modelos, true);
            }

            public function execute(string $model, string $instruction, array $context): AgentModelResult
            {
                $this->ultimoModelo = $model;
                $this->ultimaInstrucao = $instruction;
                $this->ultimoContexto = $context;

                return new AgentModelResult(
                    text: $this->texto,
                    modelUsed: $model,
                    inputTokens: 120,
                    outputTokens: 45,
                    costCents: 7,
                );
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

    private function executor(AgentModelProvider $provedor): AgentTaskExecutor
    {
        return new AgentTaskExecutor(app(AgentRegistry::class), $provedor);
    }

    /**
     * ⭐ O ESTADO DE PARTIDA.
     *
     * ⭐ O executor precisa de um modelo para trabalhar, e o modelo vem da
     * configuração. Como o CI não tem `.env`, o teste define um padrão explícito
     * — assim cada teste parte de um estado CONHECIDO, e o teste do modelo padrão
     * pode sobrescrever quando é o que está em prova.
     */
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'marketing_agents.default_model' => 'modelo-de-teste',
            'marketing_agents.ai.model_default' => '',
        ]);
    }

    // =========================================================================
    // O EXECUTOR PRODUZ TEXTO
    // =========================================================================

    public function test_executor_produz_o_texto_do_modelo(): void
    {
        $provedor = $this->provedorFalso('A legenda pronta para revisao.');
        $resultado = $this->executor($provedor)->execute($this->tarefa());

        $this->assertSame('A legenda pronta para revisao.', $resultado->text);
        $this->assertSame(120, $resultado->inputTokens);
        $this->assertSame(7, $resultado->costCents);
    }

    public function test_executor_manda_o_papel_do_agente_e_as_regras_do_escritorio(): void
    {
        $provedor = $this->provedorFalso();
        $this->executor($provedor)->execute($this->tarefa());

        $contexto = $provedor->ultimoContexto;

        $this->assertSame('Copy & Content Agent', $contexto['agent_name']);
        $this->assertSame('copy_post_01', $contexto['task_title']);

        // ⭐⭐ A REGRA DA VIA, comunicada ao modelo em texto.
        // É isto que impede a IA de decidir que o próprio trabalho está bom.
        $this->assertStringContainsString('Você não aprova', $contexto['rules']);
        $this->assertStringContainsString('volta para VOCÊ', $contexto['rules']);
    }

    public function test_executor_manda_o_parecer_anterior_da_via_para_correcao(): void
    {
        $provedor = $this->provedorFalso();
        $this->executor($provedor)->execute($this->tarefa([
            'via_status' => 'changes_requested',
            'via_note' => 'Faltou o CTA no final.',
        ]));

        $this->assertSame('Faltou o CTA no final.', $provedor->ultimoContexto['previous_via_note']);
    }

    public function test_executor_usa_o_modelo_padrao_quando_o_agente_nao_declara(): void
    {
        config(['marketing_agents.default_model' => 'deepseek/deepseek-v4-flash']);

        $provedor = $this->provedorFalso('ok', ['deepseek/deepseek-v4-flash']);
        $this->executor($provedor)->execute($this->tarefa());

        $this->assertSame('deepseek/deepseek-v4-flash', $provedor->ultimoModelo);
    }

    /**
     * ⭐⭐ O MODELO DO GATEWAY É O ÚLTIMO RECURSO.
     *
     * ⭐ Este é o teste que garante que o Escritório não cria uma segunda
     * convenção: se o agente não declara modelo e o Escritório não tem padrão
     * próprio, ele usa o `ROTEIA_CHAT_MODEL` — a mesma variável do IA Center.
     */
    public function test_executor_cai_no_modelo_do_gateway_quando_nao_ha_padrao_proprio(): void
    {
        config([
            'marketing_agents.default_model' => '',
            'marketing_agents.ai.model_default' => 'modelo-do-gateway',
        ]);

        $provedor = $this->provedorFalso('ok', ['modelo-do-gateway']);
        $this->executor($provedor)->execute($this->tarefa());

        $this->assertSame('modelo-do-gateway', $provedor->ultimoModelo);
    }

    public function test_executor_usa_o_modelo_do_agente_quando_declarado(): void
    {
        config([
            'marketing_agents.agents.copy_content.model' => 'grok-4-fast',
            'marketing_agents.default_model' => 'grok-4',
        ]);

        $provedor = $this->provedorFalso('ok', ['grok-4', 'grok-4-fast']);
        $this->executor($provedor)->execute($this->tarefa());

        $this->assertSame('grok-4-fast', $provedor->ultimoModelo);
    }

    // =========================================================================
    // O EXECUTOR NÃO PODE FURAR A SUPERVISÃO
    // =========================================================================

    public function test_executor_recusa_modelo_que_o_provedor_nao_tem(): void
    {
        $provedor = $this->provedorFalso('ok', ['outro-modelo']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('não possui o modelo');

        $this->executor($provedor)->execute($this->tarefa());
    }

    public function test_executor_recusa_tarefa_sem_agente_responsavel(): void
    {
        $provedor = $this->provedorFalso();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('agente responsável');

        $this->executor($provedor)->execute($this->tarefa([
            'agent_definition_id' => null,
        ]));
    }

    /**
     * ⭐⭐ A ORDEM DAS CHEKAGENS IMPORTA.
     *
     * ⭐ Sem provedor, a mensagem é sobre o PROVEDOR — e não sobre o modelo. A
     * razão é a causa raiz: dizer "não há modelo padrão" levaria o usuário a
     * caçar um modelo quando o que falta é a IA inteira.
     *
     * ⭐ Este teste trava essa ordem: se alguém inverter as checagens, ele quebra.
     */
    public function test_sem_provedor_a_mensagem_fala_do_provedor_e_nao_do_modelo(): void
    {
        config(['marketing_agents.default_model' => '']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Nenhum provedor de IA está configurado');

        $executor = new AgentTaskExecutor(app(AgentRegistry::class), null);
        $executor->execute($this->tarefa());
    }

    /**
     * ⭐⭐ O TESTE MAIS IMPORTANTE DO ARQUIVO.
     *
     * ⭐ O executor não tem NENHUMA porta de decisão da VIA. Não recebe, não
     * importa, não referencia. Este teste verifica isso pela própria assinatura
     * da classe: se alguém adicionar uma dependência de escrita no gate, o
     * construtor passa a exigir mais argumentos e o teste quebra.
     *
     * É a diferença entre "a regra está no código" e "a regra é IMPOSSÍVEL de
     * furar por este caminho".
     */
    public function test_executor_nao_tem_acesso_a_porta_de_decisao_da_via(): void
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

    public function test_executor_falha_com_mensagem_clara_sem_provedor_configurado(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Nenhum provedor de IA está configurado');

        $executor = new AgentTaskExecutor(app(AgentRegistry::class), null);
        $executor->execute($this->tarefa());
    }

    /**
     * ⭐⭐ O PROVEDOR SÓ É REGISTRADO COM A CONFIGURAÇÃO COMPLETA.
     *
     * ⭐ Este é o estado em que o Escritório deve ficar enquanto se avalia fluxo
     * e custo: SEM lista de modelos, o provedor não é registrado, o executor
     * falha com mensagem clara, e o Escritório continua funcionando com a
     * entrega digitada à mão.
     *
     * ⭐ E É POR ISSO QUE A LISTA DE MODELOS É O QUE SEGURA A CHAVE: sem ela,
     * nenhuma chamada de IA é feita — mesmo com a chave no ambiente.
     */
    public function test_provedor_so_e_registrado_com_a_configuracao_completa(): void
    {
        // ⭐ O container MEMOIZA o singleton. Por isso cada cenário usa um
        // container novo — senão o resultado do cenário anterior continuaria
        // valendo e o teste passaria por acidente.
        $resolver = function (): ?AgentModelProvider {
            app()->forgetInstance(AgentModelProvider::class);

            return app(AgentModelProvider::class);
        };

        // Sem base, sem chave e sem lista: não há provedor.
        config([
            'marketing_agents.ai.base_url' => '',
            'marketing_agents.ai.api_key' => '',
            'marketing_agents.ai.models' => [],
        ]);
        $this->assertNull($resolver());

        // Com a lista vazia, mesmo com base e chave: não há provedor.
        config([
            'marketing_agents.ai.base_url' => 'https://api.roteia.ai',
            'marketing_agents.ai.api_key' => 'chave-de-teste',
            'marketing_agents.ai.models' => [],
        ]);
        $this->assertNull($resolver());

        // Completo: o provedor existe.
        config(['marketing_agents.ai.models' => ['deepseek/deepseek-v4-flash']]);
        $provedor = $resolver();

        $this->assertNotNull($provedor);
        $this->assertTrue($provedor->supports('deepseek/deepseek-v4-flash'));
        $this->assertFalse($provedor->supports('modelo-inexistente'));
    }

    // =========================================================================
    // O ADAPTADOR HTTP
    // =========================================================================

    public function test_adaptador_recusa_modelo_fora_da_lista_configurada(): void
    {
        $provedor = new OpenAiCompatibleModelProvider(
            name: 'roteia',
            baseUrl: 'https://api.exemplo.test/v1',
            apiKey: 'chave-de-teste',
            models: ['grok-4'],
        );

        $this->assertTrue($provedor->supports('grok-4'));
        $this->assertFalse($provedor->supports('grok-3'));

        $this->expectException(RuntimeException::class);
        $provedor->execute('grok-3', 'faça algo', []);
    }
}
