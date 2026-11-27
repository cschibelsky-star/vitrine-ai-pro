<?php

/**
 * O QUE É: o provider que liga o executor de IA e o provedor de modelo.
 * COMO FUNCIONA: registra o `AgentTaskExecutor` no container, montando o
 * provedor a partir da MESMA configuração que o IA Center já usa.
 * FUNÇÃO NO SISTEMA: ⭐ ser o ÚNICO lugar onde se decide qual IA o Escritório usa.
 *
 * ⭐⭐ E A REGRA MAIS IMPORTANTE DESTE ARQUIVO:
 *
 *   ⭐ NÃO EXISTE UMA SEGUNDA CONVENÇÃO DE VARIÁVEIS.
 *
 *   A plataforma já tem `ROTEIA_BASE_URL`, `ROTEIA_API_KEY` e `ROTEIA_CHAT_MODEL`,
 *   usadas pelo `AiExecutionService`. Se o Escritório usasse `IA_URL_BASE` e
 *   `IA_CHAVE`, teríamos DUAS chaves para o mesmo provedor, e o dia que uma
 *   acabasse e a outra não, o produto quebraria de um jeito difícil de
 *   diagnosticar.
 *
 *   ⭐ Então o Escritório LÊ AS MESMAS VARIÁVEIS. O que é novo são só as duas
 *   que não existiam: a lista de modelos aceitos e o modelo padrão dos agentes.
 */

declare(strict_types=1);

namespace App\Providers;

use App\Marketing\Domain\Agents\AgentModelProvider;
use App\Marketing\Domain\Agents\AgentRegistry;
use App\Marketing\Domain\Agents\AgentTaskExecutor;
use App\Marketing\Domain\Agents\AvelloExecutionAdapter;
use App\Marketing\Domain\Agents\OpenAiCompatibleModelProvider;
use App\Marketing\Domain\Agents\RoutedModelProvider;
use App\Services\Ai\AiRoutingService;
use Illuminate\Support\ServiceProvider;

final class AgentExecutionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ⭐⭐ O AVELLO — o EXECUTOR INICIAL da arquitetura.
        //
        // ⭐⭐ O PR #172 estabelece: "Avello is the initial inference executor for
        // DEV/ECO mission calls". ⭐ Ele vem PRIMEIRO na ordem, e só é usado com
        // transporte autenticado explicitamente autorizado.
        //
        // ⭐⭐ E, como no contrato do PR #172, ⭐ o transporte é INJETADO e
        // DESLIGADO por padrão: ⭐ sem `AVELLO_TRANSPORT` configurado, o Avello
        // responde "desativado" — e ⭐ nenhuma chamada de inferência acontece.
        //
        // ⭐⭐ A ORDEM IMPORTA: ⭐ o Avello é o executor inicial, e o RoteirO é o
        // fallback. ⭐ É a arquitetura aprovada, e não uma escolha minha.
        $this->app->singleton(AvelloExecutionAdapter::class, function ($app): AvelloExecutionAdapter {
            return new AvelloExecutionAdapter(
                routing: $app->make(AiRoutingService::class),
                transport: $this->transporteDoAvello(),
                enabled: (bool) config('marketing_agents.avello.enabled'),
            );
        });

        // ⭐ O PROVEDOR DE MODELO — em DUAS variantes, porque elas servem a
        // momentos diferentes do produto:
        //
        // 1. `AgentModelProvider` (o roteado) — o padrão. ⭐ Ele delega a ORDEM
        //    de preferência ao `AiRoutingService` e cai no próximo provedor
        //    quando um falha. É o que dá resiliência: se o RoteirO estiver fora
        //    do ar, o trabalho continua no Claude Opus 5.
        //
        // 2. `AgentModelProviderSimples` (o de URL fixa) — para quando se quer
        //    um provedor específico, sem fallback. Fica registrado sob outro
        //    alias.
        //
        // ⭐ E A REGRA DE OURO: ⭐ SEM CONFIGURAÇÃO, NÃO HÁ PROVEDOR. O
        // Escritório registra `null` em vez de estourar — ele precisa continuar
        // funcionando (a entrega pode ser digitada à mão), e é só quando alguém
        // pede uma execução que o erro aparece, com mensagem clara.
        $this->app->singleton(AgentModelProvider::class, function ($app): ?AgentModelProvider {
            $baseUrl = trim((string) config('marketing_agents.ai.base_url'));
            $apiKey = trim((string) config('marketing_agents.ai.api_key'));

            // ⭐ Sem credencial do gateway, não há roteamento possível: os
            // provedores do IA Center dependem dela para o RoteirO, e os demais
            // precisam de credencial própria.
            if ($baseUrl === '' || $apiKey === '') {
                return null;
            }

            return new RoutedModelProvider(
                routing: $app->make(AiRoutingService::class),
                timeoutSeconds: (int) config('marketing_agents.ai.timeout_seconds', 120),
            );
        });

        // ⭐ O PROVEDOR DE URL FIXA — para quem quiser um provedor único, sem
        // fallback. Fica registrado com o próprio nome, para não confundir.
        $this->app->singleton(
            AgentModelProvider::class.'.simples',
            function (): ?AgentModelProvider {
                $baseUrl = trim((string) config('marketing_agents.ai.base_url'));
                $apiKey = trim((string) config('marketing_agents.ai.api_key'));
                $models = (array) config('marketing_agents.ai.models', []);

                if ($baseUrl === '' || $apiKey === '' || $models === []) {
                    return null;
                }

                return new OpenAiCompatibleModelProvider(
                    name: (string) config('marketing_agents.ai.provider'),
                    baseUrl: $baseUrl,
                    apiKey: $apiKey,
                    models: array_values(array_map('strval', $models)),
                    timeoutSeconds: (int) config('marketing_agents.ai.timeout_seconds', 120),
                );
            },
        );

        // ⭐ O EXECUTOR. É o único ponto que junta o registro de agentes (de onde
        // vem o modelo e a capability) com o provedor (que sabe chamar a IA).
        $this->app->singleton(AgentTaskExecutor::class, function ($app): AgentTaskExecutor {
            return new AgentTaskExecutor(
                registry: $app->make(AgentRegistry::class),
                provider: $app->make(AgentModelProvider::class),
            );
        });
    }

    /**
     * ⭐⭐ O TRANSPORTE DO AVELLO.
     *
     * ⭐⭐ Devolve `null` quando o Avello não está configurado — e ⭐ `null` é
     * exatamente o que o `AvelloExecutionAdapter` entende como "desligado".
     * ⭐⭐ Assim, ⭐ sem `AVELLO_ENABLED`, ⭐ sem endpoint e ⭐ sem credencial, ⭐
     * nenhuma inferência é feita — ⭐ e o motivo fica explícito na resposta.
     *
     * ⭐⭐ E A CREDENCIAL É DO AVELLO. ⭐ Nunca a de outro provedor: ⭐ o PR #172
     * trata o Avello como conector com identidade própria, e ⭐ misturar
     * credenciais é exatamente o risco P0 que a auditoria apontou.
     *
     * @return (callable(array<string, mixed>): array<string, mixed>)|null
     */
    private function transporteDoAvello(): ?callable
    {
        if (! (bool) config('marketing_agents.avello.enabled')) {
            return null;
        }

        $bridge = new AvelloBridge(
            baseUrl: trim((string) env('AVELLO_BASE_URL', '')),
            apiKey: trim((string) env('AVELLO_API_KEY', '')),
            timeoutSeconds: (int) config('marketing_agents.ai.timeout_seconds', 120),
        );

        return $bridge;
    }
}
