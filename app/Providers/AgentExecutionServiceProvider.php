<?php

/**
 * O QUE É: o provider que liga o executor de IA e o provedor de modelo.
 * COMO FUNCIONA: registra o `AgentTaskExecutor` no container, montando o
 * provedor a partir da configuração de ambiente.
 * FUNÇÃO NO SISTEMA: ⭐ ser o ÚNICO lugar onde se decide qual IA o Escritório usa.
 *
 * ⭐ POR QUE UM PROVIDER E NÃO UM `new` NO SERVIÇO: porque o teste precisa
 * trocar o provedor por uma implementação falsa, sem chamar IA nenhuma. Registrando
 * no container, o teste sobrescreve o binding e pronto — e o código de produção
 * continua sem saber que isso existe.
 *
 * ⭐ E A CONFIGURAÇÃO FICA NO AMBIENTE, E NÃO NO CÓDIGO: a chave da API é
 * segredo. E o provedor muda (RoteirO, Invision, OpenRouter, Ollama local) sem
 * que isso precise de deploy.
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
            $name = (string) config('marketing_agents.ai.provider');
            $baseUrl = (string) config('marketing_agents.ai.base_url');
            $apiKey = (string) config('marketing_agents.ai.api_key');
            $models = (array) config('marketing_agents.ai.models', []);

            if ($name === '' || $baseUrl === '' || $apiKey === '' || $models === []) {
                return null;
            }

            return new OpenAiCompatibleModelProvider(
                name: $name,
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
