<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Shared\AI\Services\ViaAgentRoleContracts;
use App\Shared\AI\Services\ViaDecisionContracts;
use App\Shared\AI\Services\ViaFactoryContextCollector;
use App\Shared\AI\Services\ViaHandoffValidator;
use App\Shared\AI\Services\ViaObserverMissionOrchestrator;
use App\Shared\AI\Services\ViaStructuredMissionParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ViaObserverHealth extends Command
{
    protected $signature = 'via:health';

    protected $description = 'Verifica a configuração segura do VIA Agent Hub em modo OBSERVER sem executar chamadas externas.';

    public function handle(
        ViaObserverMissionOrchestrator $orchestrator,
        ViaFactoryContextCollector $factoryContextCollector,
        ViaAgentRoleContracts $roleContracts,
        ViaDecisionContracts $decisionContracts,
        ViaHandoffValidator $handoffValidator,
        ViaStructuredMissionParser $structuredParser,
    ): int {
        $enabled = (bool) config('via_agent_hub.enabled', false);
        $mode = strtoupper((string) config('via_agent_hub.mode', ''));
        $projectId = (string) config('via_agent_hub.project_id', '');
        $allowedProjects = (array) config('ai_dev_hub.allowed_projects', []);
        $domains = (array) config('via_agent_hub.allowed_domains', []);
        $capabilities = (array) config('via_agent_hub.capabilities', []);

        $observer = $mode === 'OBSERVER';
        $projectAllowed = $projectId !== '' && in_array($projectId, $allowedProjects, true);
        $factoryAllowed = in_array('factory', $domains, true);
        $writeBlocked = ($capabilities['write'] ?? true) === false;
        $deployBlocked = ($capabilities['deploy'] ?? true) === false;
        $destructiveBlocked = ($capabilities['destructive_actions'] ?? true) === false;
        $readEnabled = ($capabilities['read'] ?? false) === true;
        $analyzeEnabled = ($capabilities['analyze'] ?? false) === true;
        $recommendEnabled = ($capabilities['recommend'] ?? false) === true;

        $pipelineReady = false;
        $pipelineStages = 0;
        $runtimeReady = false;
        $structuredRuntimeReady = false;
        $collectorReady = false;
        $evidencePackReady = false;
        $missionRecordReady = false;
        $roleContractsReady = false;
        $roleContractsCount = 0;
        $decisionContractsReady = false;
        $decisionContractsCount = 0;
        $handoffValidatorReady = false;
        $persistenceReady = false;
        $collectorEntries = 0;
        $factoryPhpFiles = 0;
        $factoryCommands = 0;
        $factoryRoutes = 0;
        $factoryStages = 0;
        $intakeSecurityReady = false;
        $intakeEndpointState = 'unknown';
        $intakeRateLimit = false;
        $legacySchemaRecognized = false;
        $maxOutputTokens = 0;
        $maxContextChars = 0;
        $temperature = 0.0;
        $reportFormat = '';

        try {
            $dryRun = $orchestrator->dryRun([
                'domain' => 'factory',
                'target_project_id' => 'factory',
                'mission' => 'Health check offline do pipeline VIA Observer.',
            ]);

            $pipelineStages = count((array) ($dryRun['stages'] ?? []));
            $pipelineReady = $pipelineStages === 6
                && (int) ($dryRun['external_ai_calls'] ?? -1) === 0
                && (int) ($dryRun['writes'] ?? -1) === 0
                && (int) ($dryRun['deploys'] ?? -1) === 0
                && (int) ($dryRun['destructive_actions'] ?? -1) === 0;

            $runtime = (array) ($dryRun['runtime'] ?? []);
            $maxOutputTokens = (int) ($runtime['max_output_tokens'] ?? 0);
            $maxContextChars = (int) ($runtime['max_context_chars'] ?? 0);
            $temperature = (float) ($runtime['temperature'] ?? -1);
            $reportFormat = (string) ($runtime['report_format'] ?? '');

            $runtimeReady = $reportFormat === 'compact_v1'
                && $maxOutputTokens >= 200
                && $maxOutputTokens <= 4000
                && $maxContextChars >= 2000
                && $maxContextChars <= 50000
                && $temperature >= 0.0
                && $temperature <= 0.3
                && data_get($runtime, 'thinking') === 'disabled'
                && data_get($runtime, 'grounding_policy') === 'strict_v1';

            $evidencePackReady = data_get($runtime, 'evidence_pack') === 'evidence_pack_v1';
            $missionRecordReady = data_get($runtime, 'mission_record') === 'mission_record_v1';

            $roleValidation = $roleContracts->validate();
            $roleContractsCount = (int) ($roleValidation['roles'] ?? 0);
            $roleContractsReady = ($roleValidation['valid'] ?? false) === true
                && ($roleValidation['version'] ?? null) === ViaAgentRoleContracts::VERSION
                && $roleContractsCount === 6
                && data_get($runtime, 'role_contracts') === ViaAgentRoleContracts::VERSION;

            $decisionValidation = $decisionContracts->validateDefinition();
            $decisionContractsCount = (int) ($decisionValidation['roles'] ?? 0);
            $decisionContractsReady = ($decisionValidation['valid'] ?? false) === true
                && ($decisionValidation['version'] ?? null) === ViaDecisionContracts::VERSION
                && $decisionContractsCount === 6
                && data_get($runtime, 'decision_contracts') === ViaDecisionContracts::VERSION;

            foreach ((array) ($dryRun['stages'] ?? []) as $stage) {
                $contract = (array) ($stage['contract'] ?? []);
                $decisionContract = (array) ($stage['decision_contract'] ?? []);

                $roleContractsReady = $roleContractsReady
                    && ($contract['mode'] ?? null) === 'OBSERVER'
                    && ($contract['permissions']['write'] ?? true) === false
                    && ($contract['permissions']['deploy'] ?? true) === false
                    && ($contract['permissions']['destructive_actions'] ?? true) === false;

                $decisionContractsReady = $decisionContractsReady
                    && ($decisionContract['permissions']['write'] ?? true) === false
                    && ($decisionContract['permissions']['deploy'] ?? true) === false
                    && ($decisionContract['permissions']['destructive_actions'] ?? true) === false
                    && ($decisionContract['required_fields'] ?? []) !== []
                    && ($decisionContract['decision_states'] ?? []) === ['ready', 'blocked', 'needs_evidence', 'requires_owner_authorization'];
            }

            $sampleDecision = [
                'mission_scope' => 'health_check',
                'critical_dependencies' => ['evidence_pack'],
                'handoff_to_project_manager' => 'continue',
                'decision_state' => 'ready',
                'evidence_refs' => ['health:offline'],
                'execution_claimed' => false,
            ];
            $sampleHandoff = $handoffValidator->validate('orchestrator', $sampleDecision);
            $handoffValidatorReady = ($sampleHandoff['version'] ?? null) === ViaHandoffValidator::VERSION
                && ($sampleHandoff['valid'] ?? false) === true
                && ($sampleHandoff['handoff_allowed'] ?? false) === true
                && ($sampleHandoff['next_role'] ?? null) === 'project_manager'
                && ($sampleHandoff['blocked'] ?? true) === false;

            $sampleStructured = json_encode([
                'runtime' => ['version' => 'structured_mission_runtime_v1', 'observer_mode' => true],
                'decisions' => [
                    'orchestrator' => [
                        'mission_scope' => 'health_check',
                        'critical_dependencies' => ['evidence_pack'],
                        'handoff_to_project_manager' => 'continue',
                        'decision_state' => 'ready',
                        'evidence_refs' => ['health:offline'],
                        'execution_claimed' => false,
                    ],
                    'project_manager' => [
                        'priority' => ['health'],
                        'sequence' => ['validate'],
                        'blockers' => [],
                        'acceptance_criteria' => ['offline_ok'],
                        'decision_state' => 'ready',
                        'evidence_refs' => ['health:offline'],
                        'execution_claimed' => false,
                    ],
                    'architect' => [
                        'architecture_findings' => ['observer'],
                        'integration_impact' => 'none',
                        'security_impact' => 'none',
                        'reversibility' => 'not_applicable',
                        'decision_state' => 'ready',
                        'evidence_refs' => ['health:offline'],
                        'execution_claimed' => false,
                    ],
                    'qa' => [
                        'validation_plan' => ['offline'],
                        'test_cases' => ['parser_chain'],
                        'expected_results' => ['valid'],
                        'decision_state' => 'ready',
                        'evidence_refs' => ['health:offline'],
                        'execution_claimed' => false,
                    ],
                    'auditor' => [
                        'grounding_review' => 'ok',
                        'risk_review' => 'ok',
                        'permission_review' => 'observer_only',
                        'owner_authorization_items' => [],
                        'decision_state' => 'ready',
                        'evidence_refs' => ['health:offline'],
                        'execution_claimed' => false,
                    ],
                    'report' => [
                        'diagnosis' => ['ok'],
                        'evidence' => ['health:offline'],
                        'evidence_gaps' => [],
                        'risks' => [],
                        'recommendations' => [],
                        'validation_criteria' => ['structured_runtime_valid'],
                        'decision_state' => 'ready',
                        'evidence_refs' => ['health:offline'],
                        'execution_claimed' => false,
                    ],
                ],
                'report' => 'health structured runtime ok',
            ], JSON_THROW_ON_ERROR);

            $parsedStructured = $structuredParser->parse($sampleStructured);
            $structuredChain = $handoffValidator->validateChain((array) ($parsedStructured['decisions'] ?? []));
            $structuredRuntimeReady = ($parsedStructured['version'] ?? null) === ViaStructuredMissionParser::VERSION
                && ($parsedStructured['valid'] ?? false) === true
                && ($structuredChain['version'] ?? null) === ViaHandoffValidator::VERSION
                && ($structuredChain['valid'] ?? false) === true
                && data_get($runtime, 'version') === 'structured_mission_runtime_v1'
                && data_get($runtime, 'structured_output') === 'json_contract_v1'
                && data_get($runtime, 'parser') === ViaStructuredMissionParser::VERSION
                && data_get($runtime, 'handoff_validator') === ViaHandoffValidator::VERSION;

            $persistenceReady = Schema::hasTable('via_conversations')
                && Schema::hasTable('via_messages')
                && Schema::hasColumns('via_conversations', ['user_id', 'title', 'domain', 'target_project_id', 'mode', 'last_activity_at'])
                && Schema::hasColumns('via_messages', ['via_conversation_id', 'role', 'content', 'metadata']);

            $collector = $factoryContextCollector->collect();
            $collectorEntries = (int) data_get($collector, 'sources.commercial_intake_storage.entries', 0);
            $factoryPhpFiles = (int) data_get($collector, 'sources.factory_code_inventory.app_factory.php_files', 0)
                + (int) data_get($collector, 'sources.factory_code_inventory.app_commercial_factory.php_files', 0);
            $factoryCommands = (int) data_get($collector, 'sources.factory_commands.count', 0);
            $factoryRoutes = (int) data_get($collector, 'sources.factory_routes.count', 0);
            $factoryStages = count((array) data_get($collector, 'sources.factory_stages', []));

            $intakeEndpointState = (string) data_get($collector, 'sources.intake_security.endpoint_state', 'unknown');
            $intakeRateLimit = (bool) data_get($collector, 'sources.intake_security.specific_rate_limit_observed', false);
            $intakeSecurityReady = (bool) data_get($collector, 'sources.intake_security.authorization_method_present', false)
                && (bool) data_get($collector, 'sources.intake_security.payload_validation_present', false)
                && $intakeRateLimit
                && in_array($intakeEndpointState, ['authenticated_ready', 'fail_closed_token_missing'], true);

            $legacySchemaRecognized = (bool) data_get($collector, 'schema_assessment.historical_record_may_predate_current_schema', false);

            $collectorReady = ($collector['collector'] ?? null) === 'factory_local_readonly_v2'
                && ($collector['read_only'] ?? false) === true
                && $factoryStages === 10
                && (int) data_get($collector, 'safety.files_written', -1) === 0
                && (int) data_get($collector, 'safety.commands_executed', -1) === 0
                && (int) data_get($collector, 'safety.deploys', -1) === 0
                && (int) data_get($collector, 'safety.destructive_actions', -1) === 0;
        } catch (Throwable $e) {
            report($e);
        }

        $this->table(['Verificação', 'Status'], [
            ['VIA Agent Hub habilitado', $enabled ? 'OK' : 'PENDENTE'],
            ['Modo OBSERVER', $observer ? 'OK' : 'PENDENTE'],
            ['Projeto VIA autorizado no AI Dev Hub', $projectAllowed ? 'OK' : 'PENDENTE'],
            ['Domínio Factory autorizado', $factoryAllowed ? 'OK' : 'PENDENTE'],
            ['Leitura habilitada', $readEnabled ? 'OK' : 'PENDENTE'],
            ['Análise habilitada', $analyzeEnabled ? 'OK' : 'PENDENTE'],
            ['Recomendação habilitada', $recommendEnabled ? 'OK' : 'PENDENTE'],
            ['Escrita bloqueada', $writeBlocked ? 'OK' : 'PENDENTE'],
            ['Deploy bloqueado', $deployBlocked ? 'OK' : 'PENDENTE'],
            ['Ações destrutivas bloqueadas', $destructiveBlocked ? 'OK' : 'PENDENTE'],
            ['Pipeline OBSERVER offline', $pipelineReady ? 'OK' : 'PENDENTE'],
            ['Estágios do pipeline', $pipelineReady ? (string) $pipelineStages : 'PENDENTE'],
            ['Agent Role Contracts v1', $roleContractsReady ? 'OK' : 'PENDENTE'],
            ['Papéis com contrato', $roleContractsReady ? (string) $roleContractsCount : 'PENDENTE'],
            ['Decision Contracts v1', $decisionContractsReady ? 'OK' : 'PENDENTE'],
            ['Papéis com decisão contratada', $decisionContractsReady ? (string) $decisionContractsCount : 'PENDENTE'],
            ['Handoff Validator v1', $handoffValidatorReady ? 'OK' : 'PENDENTE'],
            ['Structured Mission Runtime v1', $structuredRuntimeReady ? 'OK' : 'PENDENTE'],
            ['Conversation Persistence v1', $persistenceReady ? 'OK' : 'PENDENTE'],
            ['Mission Runtime compact_v1 + strict_v1', $runtimeReady ? 'OK' : 'PENDENTE'],
            ['Evidence Pack v1', $evidencePackReady ? 'OK' : 'PENDENTE'],
            ['Mission Record v1', $missionRecordReady ? 'OK' : 'PENDENTE'],
            ['Limite de saída', $runtimeReady ? $maxOutputTokens . ' tokens' : 'PENDENTE'],
            ['Limite de contexto', $runtimeReady ? $maxContextChars . ' chars' : 'PENDENTE'],
            ['Temperatura', $runtimeReady ? number_format($temperature, 2, '.', '') : 'PENDENTE'],
            ['Thinking desabilitado', $runtimeReady ? 'OK' : 'PENDENTE'],
            ['Factory Context Collector read-only v2', $collectorReady ? 'OK' : 'PENDENTE'],
            ['Intakes locais Factory', $collectorReady ? (string) $collectorEntries : 'PENDENTE'],
            ['Arquivos PHP Factory inventariados', $collectorReady ? (string) $factoryPhpFiles : 'PENDENTE'],
            ['Comandos Factory registrados', $collectorReady ? (string) $factoryCommands : 'PENDENTE'],
            ['Rotas Factory registradas', $collectorReady ? (string) $factoryRoutes : 'PENDENTE'],
            ['Estágios Factory conhecidos', $collectorReady ? (string) $factoryStages : 'PENDENTE'],
            ['Segurança intake Factory', $intakeSecurityReady ? 'OK' : 'PENDENTE'],
            ['Rate-limit intake Factory', $intakeRateLimit ? 'OK (10/min)' : 'PENDENTE'],
            ['Estado endpoint intake', $intakeEndpointState],
            ['Schema legado reconhecido', $legacySchemaRecognized ? 'OK' : 'NÃO APLICÁVEL'],
        ]);

        $ready = $enabled
            && $observer
            && $projectAllowed
            && $factoryAllowed
            && $readEnabled
            && $analyzeEnabled
            && $recommendEnabled
            && $writeBlocked
            && $deployBlocked
            && $destructiveBlocked
            && $pipelineReady
            && $roleContractsReady
            && $decisionContractsReady
            && $handoffValidatorReady
            && $structuredRuntimeReady
            && $persistenceReady
            && $runtimeReady
            && $evidencePackReady
            && $missionRecordReady
            && $collectorReady
            && $intakeSecurityReady;

        if ($ready) {
            $this->info('VIA Agent Hub pronto em modo OBSERVER com Structured Mission Runtime v1, conversa persistente e rastreabilidade auditável. Nenhuma chamada externa foi executada.');
            return self::SUCCESS;
        }

        $this->warn('VIA Agent Hub possui pendências de segurança/configuração.');
        return self::INVALID;
    }
}
