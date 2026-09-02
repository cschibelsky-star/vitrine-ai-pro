<?php

declare(strict_types=1);

namespace App\Shared\AI\Services;

use RuntimeException;

class ViaObserverMissionOrchestrator
{
    private const RUNTIME_VERSION = 'structured_mission_runtime_v1';

    private const STAGES = [
        'orchestrator',
        'project_manager',
        'architect',
        'qa',
        'auditor',
        'report',
    ];

    public function __construct(
        private readonly ViaObserverGateway $gateway,
        private readonly ViaFactoryContextCollector $factoryContextCollector,
        private readonly ViaMissionEvidencePack $evidencePack,
        private readonly ViaAgentRoleContracts $roleContracts,
        private readonly ViaDecisionContracts $decisionContracts,
        private readonly ViaHandoffValidator $handoffValidator,
        private readonly ViaStructuredMissionParser $structuredParser,
    ) {
    }

    public function dryRun(array $request): array
    {
        $mission = trim((string) ($request['mission'] ?? ''));
        $domain = (string) ($request['domain'] ?? config('via_agent_hub.default_domain', 'factory'));
        $targetProjectId = trim((string) ($request['target_project_id'] ?? ''));

        if ($mission === '') {
            throw new RuntimeException('Missão não informada.');
        }

        $capabilities = $this->gateway->capabilities();

        if (($capabilities['mode'] ?? null) !== 'OBSERVER') {
            throw new RuntimeException('Pipeline VIA disponível somente em modo OBSERVER nesta fase.');
        }

        if (! in_array($domain, (array) ($capabilities['allowed_domains'] ?? []), true)) {
            throw new RuntimeException('Domínio não autorizado para o pipeline VIA.');
        }

        return [
            'mode' => 'OBSERVER',
            'execution' => 'dry-run',
            'runtime' => [
                'version' => self::RUNTIME_VERSION,
                'report_format' => (string) config('via_agent_hub.mission_runtime.report_format', 'compact_v1'),
                'structured_output' => 'json_contract_v1',
                'parser' => ViaStructuredMissionParser::VERSION,
                'handoff_validator' => ViaHandoffValidator::VERSION,
                'max_output_tokens' => $this->maxOutputTokens(),
                'max_context_chars' => $this->maxContextChars(),
                'temperature' => $this->temperature(),
                'thinking' => 'disabled',
                'grounding_policy' => 'strict_v1',
                'evidence_pack' => 'evidence_pack_v1',
                'mission_record' => 'mission_record_v1',
                'role_contracts' => ViaAgentRoleContracts::VERSION,
                'decision_contracts' => ViaDecisionContracts::VERSION,
            ],
            'domain' => $domain,
            'target_project_id' => $targetProjectId !== '' ? $targetProjectId : null,
            'mission' => $mission,
            'context_collector' => $domain === 'factory' ? 'factory_local_readonly_v2' : null,
            'stages' => array_map(fn (string $stage): array => [
                'stage' => $stage,
                'status' => 'ready',
                'contract' => $this->roleContracts->get($stage),
                'decision_contract' => $this->decisionContracts->get($stage),
            ], self::STAGES),
            'external_ai_calls' => 0,
            'writes' => 0,
            'deploys' => 0,
            'destructive_actions' => 0,
            'owner_authorization_required_for_execution' => true,
        ];
    }

    public function buildEvidencePack(array $request): array
    {
        $dryRun = $this->dryRun($request);
        $evidence = match ($dryRun['domain']) {
            'factory' => $this->factoryContextCollector->collect(),
            default => [],
        };

        return $this->evidencePack->build(
            $dryRun['domain'],
            $dryRun['target_project_id'],
            $dryRun['mission'],
            $evidence,
        );
    }

    public function execute(array $request): array
    {
        $dryRun = $this->dryRun($request);
        $pack = $this->buildEvidencePack($request);
        $context = $this->pipelineContext()
            . "\n\nMISSION_EVIDENCE_PACK_V1:\n"
            . $this->evidencePack->compact($pack);

        $context = $this->truncateContext($context);

        $analysis = $this->gateway->analyze([
            'domain' => $dryRun['domain'],
            'target_project_id' => $dryRun['target_project_id'],
            'mission' => $dryRun['mission'],
            'context' => $context,
            'structured' => true,
            'provider' => $request['provider'] ?? null,
            'model' => $request['model'] ?? null,
            'profile' => $request['profile'] ?? config('via_agent_hub.default_profile', 'balanced'),
            'options' => [
                'temperature' => $this->temperature(),
                'max_tokens' => $this->maxOutputTokens(),
                'response_format' => ['type' => 'json_object'],
                'thinking' => ['type' => 'disabled'],
            ],
            'audit_metadata' => [
                'mission_id' => $pack['mission_id'],
                'evidence_pack_version' => $pack['version'],
                'evidence_pack_sha256' => $pack['sha256'],
                'runtime_version' => self::RUNTIME_VERSION,
                'structured_parser_version' => ViaStructuredMissionParser::VERSION,
                'handoff_validator_version' => ViaHandoffValidator::VERSION,
                'grounding_policy' => 'strict_v1',
                'observer_mode' => true,
                'domain' => $dryRun['domain'],
                'target_project_id' => $dryRun['target_project_id'],
            ],
        ]);

        $content = (string) data_get($analysis, 'analysis.content', '');
        $parsed = $this->structuredParser->parse($content);

        $parserErrors = (array) ($parsed['errors'] ?? []);
        $jsonFailure = ($parsed['valid'] ?? false) !== true && array_filter(
            $parserErrors,
            static fn (mixed $error): bool => str_starts_with((string) $error, 'json_')
        ) !== [];

        if ($jsonFailure && trim($content) !== '') {
            $repairAnalysis = $this->gateway->analyze([
                'domain' => $dryRun['domain'],
                'target_project_id' => $dryRun['target_project_id'],
                'mission' => 'Repare exclusivamente a serialização JSON da resposta estruturada abaixo. Preserve o significado, os seis papéis, os decision_state e as evidências já declaradas. Não adicione fatos, não execute ações e não escreva explicações fora do JSON.',
                'context' => "RESPOSTA_JSON_INVALIDA_PARA_REPARO:\n" . mb_substr($content, 0, 24000),
                'structured' => true,
                'provider' => $request['provider'] ?? null,
                'model' => $request['model'] ?? null,
                'profile' => $request['profile'] ?? config('via_agent_hub.default_profile', 'balanced'),
                'options' => [
                    'temperature' => 0.0,
                    'max_tokens' => $this->maxOutputTokens(),
                    'response_format' => ['type' => 'json_object'],
                    'thinking' => ['type' => 'disabled'],
                ],
                'audit_metadata' => [
                    'mission_id' => $pack['mission_id'],
                    'runtime_version' => self::RUNTIME_VERSION,
                    'repair_attempt' => 1,
                    'repair_reason' => implode(',', $parserErrors),
                    'observer_mode' => true,
                ],
            ]);

            $repairContent = (string) data_get($repairAnalysis, 'analysis.content', '');
            $repairParsed = $this->structuredParser->parse($repairContent);

            if (($repairParsed['valid'] ?? false) === true) {
                $analysis = $repairAnalysis;
                $content = $repairContent;
                $parsed = $repairParsed;
            }
        }

        $decisions = (array) ($parsed['decisions'] ?? []);
        $chain = $parsed['valid']
            ? $this->handoffValidator->validateChain($decisions)
            : $this->invalidChainFallback((array) ($parsed['errors'] ?? []));

        $structuredValid = ($parsed['valid'] ?? false) === true && ($chain['valid'] ?? false) === true;
        $executionReady = $structuredValid && ($chain['execution_ready'] ?? false) === true;
        $report = $structuredValid
            ? trim((string) ($parsed['report'] ?? ''))
            : $this->safeFallbackReport($parsed, $chain);

        if ($structuredValid && $report === '') {
            $report = 'Análise estruturada concluída em modo OBSERVER. O contrato é válido, mas o relatório textual veio vazio. Nenhuma ação foi executada.';
        }

        return [
            'pipeline' => $dryRun,
            'role_contracts' => [
                'version' => ViaAgentRoleContracts::VERSION,
                'validation' => $this->roleContracts->validate(),
            ],
            'decision_contracts' => [
                'version' => ViaDecisionContracts::VERSION,
                'validation' => $this->decisionContracts->validateDefinition(),
            ],
            'structured_runtime' => [
                'version' => self::RUNTIME_VERSION,
                'parser_version' => ViaStructuredMissionParser::VERSION,
                'parser_valid' => (bool) ($parsed['valid'] ?? false),
                'handoff_validator_version' => ViaHandoffValidator::VERSION,
                'chain_valid' => (bool) ($chain['valid'] ?? false),
                'valid' => $structuredValid,
                'execution_ready' => $executionReady,
                'fail_closed' => ! $executionReady,
                'decisions' => $decisions,
                'chain' => $chain,
            ],
            'evidence_pack' => [
                'version' => $pack['version'],
                'mission_id' => $pack['mission_id'],
                'sha256' => $pack['sha256'],
                'collected_at' => $pack['collected_at'],
            ],
            'mission_record' => [
                'version' => 'mission_record_v1',
                'mission_id' => $pack['mission_id'],
                'evidence_pack_sha256' => $pack['sha256'],
                'runtime_version' => self::RUNTIME_VERSION,
                'structured_parser_version' => ViaStructuredMissionParser::VERSION,
                'handoff_validator_version' => ViaHandoffValidator::VERSION,
                'structured_runtime_valid' => $structuredValid,
                'grounding_policy' => 'strict_v1',
                'role_contracts_version' => ViaAgentRoleContracts::VERSION,
                'decision_contracts_version' => ViaDecisionContracts::VERSION,
                'observer_mode' => true,
            ],
            'report' => $report,
            'result' => $analysis,
        ];
    }

    private function pipelineContext(): string
    {
        $roleContracts = $this->roleContracts->promptBlock();
        $decisionContracts = $this->decisionContracts->promptBlock();

        return <<<CONTEXT
PIPELINE OBRIGATORIO:
1. ORCHESTRATOR
2. PROJECT_MANAGER
3. ARCHITECT
4. QA
5. AUDITOR
6. REPORT

{$roleContracts}

{$decisionContracts}

STRUCTURED_MISSION_RUNTIME_V1:
- Produza UMA única resposta JSON para toda a missão; não simule chamadas separadas.
- A raiz deve conter runtime, decisions e report.
- decisions deve conter exatamente os 6 papéis do pipeline.
- Cada papel deve preencher todos os campos obrigatórios do seu Decision Contract.
- evidence_refs deve referenciar evidências do MISSION_EVIDENCE_PACK_V1 ou marcar needs_evidence.
- execution_claimed deve ser false em todos os papéis.
- O handoff só é considerado pronto quando o papel está validamente em decision_state=ready.
- requires_owner_authorization é válido, mas não concede execução e interrompe prontidão de handoff.
- TODOS os campos abaixo devem existir em TODOS os blocos, mesmo quando o valor correto for [] ou uma string de LACUNA_DE_EVIDENCIA.
- Nunca omita campos obrigatórios para economizar tokens.

SCHEMA JSON OBRIGATORIO POR PAPEL:
{
  "runtime": {"version":"structured_mission_runtime_v1","observer_mode":true},
  "decisions": {
    "orchestrator": {
      "decision_state":"ready|blocked|needs_evidence|requires_owner_authorization",
      "evidence_refs":[],
      "execution_claimed":false,
      "mission_scope":"",
      "critical_dependencies":[],
      "handoff_to_project_manager":""
    },
    "project_manager": {
      "decision_state":"ready|blocked|needs_evidence|requires_owner_authorization",
      "evidence_refs":[],
      "execution_claimed":false,
      "priority":"",
      "sequence":[],
      "blockers":[],
      "acceptance_criteria":[]
    },
    "architect": {
      "decision_state":"ready|blocked|needs_evidence|requires_owner_authorization",
      "evidence_refs":[],
      "execution_claimed":false,
      "architecture_findings":[],
      "integration_impact":"",
      "security_impact":"",
      "reversibility":""
    },
    "qa": {
      "decision_state":"ready|blocked|needs_evidence|requires_owner_authorization",
      "evidence_refs":[],
      "execution_claimed":false,
      "validation_plan":[],
      "test_cases":[],
      "expected_results":[]
    },
    "auditor": {
      "decision_state":"ready|blocked|needs_evidence|requires_owner_authorization",
      "evidence_refs":[],
      "execution_claimed":false,
      "grounding_review":"",
      "risk_review":[],
      "permission_review":"",
      "owner_authorization_items":[]
    },
    "report": {
      "decision_state":"ready|blocked|needs_evidence|requires_owner_authorization",
      "evidence_refs":[],
      "execution_claimed":false,
      "diagnosis":"",
      "evidence":[],
      "evidence_gaps":[],
      "risks":[],
      "recommendations":[],
      "validation_criteria":[]
    }
  },
  "report":""
}
REGRAS DO SCHEMA:
- Use exatamente as seis chaves de papel acima, sem renomear e sem criar aliases.
- Se decision_state != needs_evidence, evidence_refs deve conter ao menos uma referência válida do evidence pack.
- Se decision_state = needs_evidence, preencha evidence_gaps no papel quando esse campo existir e descreva a lacuna nos campos textuais obrigatórios.
- Se decision_state = blocked, blockers deve ser preenchido no project_manager e a causa deve aparecer textualmente nos demais papéis afetados.
- Se decision_state = requires_owner_authorization, owner_authorization_items deve ser preenchido no auditor e a necessidade de autorização deve ser refletida no report.
- execution_claimed deve ser sempre false.

POLITICA DE GROUNDING STRICT_V1:
- Classifique achados em campos textuais como FATO_CONFIRMADO, INFERENCIA ou LACUNA_DE_EVIDENCIA quando aplicável.
- Ausência de informação no contexto NÃO prova ausência no sistema.
- Campo ausente, nulo ou não coletado deve ser tratado como LACUNA_DE_EVIDENCIA, salvo evidência explícita em contrário.
- Não afirmar que autenticação, rate-limit, validação, logs, middleware ou controles estão ausentes sem evidência direta.
- Não recomendar corrigir algo não confirmado; para lacunas, recomendar verificar ou coletar evidência.
- Toda análise deve ser fundamentada exclusivamente no MISSION_EVIDENCE_PACK_V1 desta missão.
- Alertas operacionais NÃO podem ser genéricos. Não use frases como "Docker requer atenção", "infraestrutura requer atenção" ou equivalentes sem evidência específica.
- Todo alerta deve identificar, quando a evidência permitir: componente afetado, estado observado, evidence_ref, severidade, impacto provável e próximo passo recomendado.
- Se a evidência não identificar componente ou estado concreto, classifique como LACUNA_DE_EVIDENCIA e recomende a coleta/verificação necessária; não produza um alerta categórico.
- Diferencie serviço permanente de job one-shot quando a evidência contiver essa distinção; encerramento esperado de job não deve ser tratado automaticamente como incidente.
- Não inferir falha de Docker apenas porque um container está exited; exigir evidência de exit_code inesperado, health degradado, indisponibilidade, restart anômalo ou outro sinal explícito.

FORMATO DE ALERTA OPERACIONAL:
- Componente: nome verificável do serviço, container, job ou subsistema, quando disponível.
- Estado observado: fato presente no evidence pack, sem extrapolação.
- Evidência: evidence_ref ou origem concreta usada na conclusão.
- Severidade: informativa, baixa, média, alta ou crítica, proporcional ao impacto comprovado.
- Impacto provável: somente quando sustentado pela evidência; caso contrário, LACUNA_DE_EVIDENCIA.
- Recomendação: próxima verificação ou ação proposta, sem alegar execução.

REGRAS DE SEGURANCA:
- Nenhum estágio pode executar escrita, deploy ou ação destrutiva.
- Nenhum estágio pode afirmar que alterou sistema, arquivo, banco, container ou infraestrutura.
- Um estágio não pode conceder autorização a outro estágio.
- Qualquer mudança proposta exige autorização explícita do owner.
CONTEXT;
    }

    private function invalidChainFallback(array $parserErrors): array
    {
        return [
            'version' => ViaHandoffValidator::VERSION,
            'valid' => false,
            'roles' => 0,
            'results' => [],
            'errors' => array_values(array_unique(array_merge(['structured_output_invalid'], $parserErrors))),
            'execution_permissions' => [
                'write' => false,
                'deploy' => false,
                'destructive_actions' => false,
            ],
        ];
    }

    private function safeFallbackReport(array $parsed, array $chain): string
    {
        $errors = array_values(array_unique(array_merge(
            (array) ($parsed['errors'] ?? []),
            (array) ($chain['errors'] ?? []),
        )));

        return 'LACUNA_DE_EVIDENCIA: a resposta estruturada da missão não passou integralmente pelo parser/validador. '
            . 'Nenhum handoff executável foi aceito e nenhuma ação foi realizada. '
            . 'Erros: ' . ($errors === [] ? 'structured_runtime_invalid' : implode(', ', $errors)) . '.';
    }

    private function truncateContext(string $context): string
    {
        $limit = $this->maxContextChars();

        if (mb_strlen($context) <= $limit) {
            return $context;
        }

        return mb_substr($context, 0, $limit) . "\n[CONTEXTO_TRUNCADO_PELO_RUNTIME]";
    }

    private function maxOutputTokens(): int
    {
        return max(1200, min(7000, (int) config('via_agent_hub.mission_runtime.max_output_tokens', 6500)));
    }

    private function maxContextChars(): int
    {
        return max(2000, min(50000, (int) config('via_agent_hub.mission_runtime.max_context_chars', 12000)));
    }

    private function temperature(): float
    {
        return max(0.0, min(1.0, (float) config('via_agent_hub.mission_runtime.temperature', 0.1)));
    }
}
