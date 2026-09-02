<?php

declare(strict_types=1);

namespace App\Shared\AI\Services;

use RuntimeException;

class ViaObserverGateway
{
    public function __construct(private readonly AiDevHubService $devHub)
    {
    }

    public function capabilities(): array
    {
        return [
            'enabled' => (bool) config('via_agent_hub.enabled', false),
            'mode' => (string) config('via_agent_hub.mode', 'OBSERVER'),
            'project_id' => (string) config('via_agent_hub.project_id', 'via-agent-hub'),
            'default_domain' => (string) config('via_agent_hub.default_domain', 'factory'),
            'allowed_domains' => array_values((array) config('via_agent_hub.allowed_domains', ['factory'])),
            'capabilities' => (array) config('via_agent_hub.capabilities', []),
        ];
    }

    public function analyze(array $request): array
    {
        $this->assertObserverReady();

        $domain = (string) ($request['domain'] ?? config('via_agent_hub.default_domain', 'factory'));
        $this->assertDomainAllowed($domain);

        $targetProject = trim((string) ($request['target_project_id'] ?? ''));
        $mission = trim((string) ($request['mission'] ?? ''));
        $context = trim((string) ($request['context'] ?? ''));
        $structured = (bool) ($request['structured'] ?? false);

        if ($mission === '') {
            throw new RuntimeException('Missão de análise não informada.');
        }

        $system = <<<'PROMPT'
Você é o VIA Agent Hub interno da Vitrine IA Pro operando estritamente em modo OBSERVER.
Sua função é analisar, diagnosticar, priorizar riscos e recomendar próximos passos.
Você NÃO possui autorização para escrever arquivos, alterar banco, executar deploy, reiniciar serviços, remover dados, executar ações destrutivas ou afirmar que realizou mudanças.
Quando uma ação executável for necessária, descreva a ação proposta e marque-a como REQUER_AUTORIZACAO_OWNER.
Priorize evidências, riscos, impacto, reversibilidade e critérios objetivos de validação.
PROMPT;

        $prompt = "DOMINIO: {$domain}\n";
        $prompt .= 'PROJETO_ALVO: ' . ($targetProject !== '' ? $targetProject : 'nao_informado') . "\n";
        $prompt .= "MISSAO:\n{$mission}\n";

        if ($context !== '') {
            $prompt .= "\nCONTEXTO_FORNECIDO:\n{$context}\n";
        }

        if ($structured) {
            $prompt .= <<<'PROMPT'

FORMATO DA RESPOSTA:
Retorne EXCLUSIVAMENTE um objeto JSON válido, sem markdown, sem texto antes ou depois.
A raiz deve conter exatamente as chaves "runtime", "decisions" e "report".
"runtime" deve informar "version":"structured_mission_runtime_v1" e "observer_mode":true.
"decisions" deve conter exatamente: orchestrator, project_manager, architect, qa, auditor, report.
Cada decisão deve obedecer integralmente ao Decision Contract fornecido no contexto.
Todos os blocos devem conter decision_state, evidence_refs e execution_claimed=false.
Não invente evidências. Quando faltar evidência, use needs_evidence. Apenas o papel report possui o campo evidence_gaps; nos demais papéis, descreva a lacuna nos campos textuais obrigatórios do próprio contrato.
Quando uma recomendação depender de alteração no sistema, use requires_owner_authorization. Apenas o papel auditor possui owner_authorization_items; nos demais papéis, reflita a necessidade nos campos textuais obrigatórios do próprio contrato.
Mantenha cada campo textual objetivo e compacto, evitando repetições entre papéis. Prefira listas curtas e preserve rigorosamente todas as chaves obrigatórias.
O campo "report" deve ser uma síntese textual curta e fundamentada das decisões estruturadas.
PROMPT;
        } else {
            $prompt .= <<<'PROMPT'

FORMATO DA RESPOSTA:
1. DIAGNOSTICO
2. EVIDENCIAS
3. RISCOS
4. RECOMENDACOES
5. ACOES_QUE_REQUEREM_AUTORIZACAO_OWNER
6. CRITERIOS_DE_VALIDACAO
PROMPT;
        }

        $result = $this->devHub->chat([
            'project_id' => (string) config('via_agent_hub.project_id', 'via-agent-hub'),
            'provider' => $request['provider'] ?? null,
            'model' => $request['model'] ?? null,
            'profile' => (string) ($request['profile'] ?? config('via_agent_hub.default_profile', 'balanced')),
            'system' => $system,
            'prompt' => $prompt,
            'options' => (array) ($request['options'] ?? []),
            'audit_metadata' => (array) ($request['audit_metadata'] ?? []),
        ]);

        return [
            'mode' => 'OBSERVER',
            'domain' => $domain,
            'target_project_id' => $targetProject !== '' ? $targetProject : null,
            'execution_permissions' => [
                'write' => false,
                'deploy' => false,
                'destructive_actions' => false,
            ],
            'analysis' => $result,
        ];
    }

    private function assertObserverReady(): void
    {
        if (! config('via_agent_hub.enabled')) {
            throw new RuntimeException('VIA Agent Hub está desabilitado.');
        }

        if (strtoupper((string) config('via_agent_hub.mode', '')) !== 'OBSERVER') {
            throw new RuntimeException('VIA Agent Hub não está configurado em modo OBSERVER.');
        }

        $capabilities = (array) config('via_agent_hub.capabilities', []);
        foreach (['write', 'deploy', 'destructive_actions'] as $forbidden) {
            if (($capabilities[$forbidden] ?? false) === true) {
                throw new RuntimeException('Configuração insegura do VIA Agent Hub detectada.');
            }
        }
    }

    private function assertDomainAllowed(string $domain): void
    {
        if ($domain === '' || ! in_array($domain, (array) config('via_agent_hub.allowed_domains', []), true)) {
            throw new RuntimeException('Domínio não autorizado para o VIA Agent Hub.');
        }
    }
}
