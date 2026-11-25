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
use App\Marketing\Domain\Agents\OpenAiCompatibleModelProvider;
use Illuminate\Support\ServiceProvider;

final class AgentExecutionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ⭐ O PROVEDOR DE MODELO.
        //
        // Se a configuração estiver incompleta, registra `null` em vez de estourar:
        // o Escritório precisa continuar funcionando (a entrega pode ser digitada à
        // mão), e é só quando alguém pede uma execução que o erro aparece, com
        // mensagem clara.
        $this->app->singleton(AgentModelProvider::class, function (): ?AgentModelProvider {
            // ⭐ As MESMAS variáveis do IA Center. Uma chave, um endpoint.
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
        });

        // ⭐ O EXECUTOR. É o único ponto que junta o registro de agentes (de onde
        // vem o modelo) com o provedor (que sabe chamar a IA).
        $this->app->singleton(AgentTaskExecutor::class, function ($app): AgentTaskExecutor {
            return new AgentTaskExecutor(
                registry: $app->make(AgentRegistry::class),
                provider: $app->make(AgentModelProvider::class),
            );
        });
    }
}
