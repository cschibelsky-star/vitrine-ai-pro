<?php

declare(strict_types=1);

namespace App\Shared\AI\Services;

use RuntimeException;

class ViaAgentRoleContracts
{
    public const VERSION = 'agent_role_contracts_v1';

    public function all(): array
    {
        return [
            'orchestrator' => $this->contract(
                role: 'orchestrator',
                purpose: 'Decompor a missão, delimitar escopo e ordenar dependências críticas sem executar ações.',
                requiredOutputs: ['mission_scope', 'critical_dependencies', 'handoff_to_project_manager'],
                forbiddenClaims: ['executed_action', 'modified_system', 'approved_execution'],
            ),
            'project_manager' => $this->contract(
                role: 'project_manager',
                purpose: 'Definir prioridade, sequência, bloqueios e critérios de aceite com base exclusiva nas evidências disponíveis.',
                requiredOutputs: ['priority', 'sequence', 'blockers', 'acceptance_criteria'],
                forbiddenClaims: ['authorized_execution', 'completed_task', 'changed_schedule_without_evidence'],
            ),
            'architect' => $this->contract(
                role: 'architect',
                purpose: 'Avaliar arquitetura, integração, segurança, reversibilidade e impacto técnico.',
                requiredOutputs: ['architecture_findings', 'integration_impact', 'security_impact', 'reversibility'],
                forbiddenClaims: ['implemented_change', 'deployed_change', 'security_absence_without_evidence'],
            ),
            'qa' => $this->contract(
                role: 'qa',
                purpose: 'Definir validações objetivas e testes mínimos necessários, sem executar testes destrutivos ou mutáveis.',
                requiredOutputs: ['validation_plan', 'test_cases', 'expected_results'],
                forbiddenClaims: ['test_executed_when_not_executed', 'production_validation_without_evidence'],
            ),
            'auditor' => $this->contract(
                role: 'auditor',
                purpose: 'Revisar grounding, riscos, permissões, conflitos e ações que exigem autorização explícita do owner.',
                requiredOutputs: ['grounding_review', 'risk_review', 'permission_review', 'owner_authorization_items'],
                forbiddenClaims: ['owner_authorized', 'risk_confirmed_without_evidence', 'permission_granted'],
            ),
            'report' => $this->contract(
                role: 'report',
                purpose: 'Consolidar as saídas dos papéis anteriores em relatório compacto, rastreável e sem ampliar conclusões.',
                requiredOutputs: ['diagnosis', 'evidence', 'evidence_gaps', 'risks', 'recommendations', 'validation_criteria'],
                forbiddenClaims: ['new_fact_not_in_evidence', 'execution_claim', 'authorization_claim'],
            ),
        ];
    }

    public function get(string $role): array
    {
        $contracts = $this->all();

        if (! isset($contracts[$role])) {
            throw new RuntimeException("Papel VIA desconhecido: {$role}");
        }

        return $contracts[$role];
    }

    public function validate(): array
    {
        $contracts = $this->all();
        $expected = ['orchestrator', 'project_manager', 'architect', 'qa', 'auditor', 'report'];
        $errors = [];

        if (array_keys($contracts) !== $expected) {
            $errors[] = 'role_order_mismatch';
        }

        foreach ($expected as $role) {
            $contract = $contracts[$role] ?? [];

            if (($contract['version'] ?? null) !== self::VERSION) {
                $errors[] = "{$role}:version";
            }
            if (($contract['role'] ?? null) !== $role) {
                $errors[] = "{$role}:role";
            }
            if (($contract['mode'] ?? null) !== 'OBSERVER') {
                $errors[] = "{$role}:mode";
            }
            if (($contract['permissions']['write'] ?? true) !== false) {
                $errors[] = "{$role}:write";
            }
            if (($contract['permissions']['deploy'] ?? true) !== false) {
                $errors[] = "{$role}:deploy";
            }
            if (($contract['permissions']['destructive_actions'] ?? true) !== false) {
                $errors[] = "{$role}:destructive_actions";
            }
            if (($contract['required_outputs'] ?? []) === []) {
                $errors[] = "{$role}:required_outputs";
            }
        }

        return [
            'version' => self::VERSION,
            'roles' => count($contracts),
            'valid' => $errors === [],
            'errors' => $errors,
        ];
    }

    public function promptBlock(): string
    {
        $lines = [
            'AGENT_ROLE_CONTRACTS_V1:',
            'Cada estágio deve respeitar seu contrato e não ampliar autoridade ou evidências.',
            'Leitura, observação, health/status, logs e coleta de evidencias em modo somente leitura podem ocorrer automaticamente dentro do escopo autorizado; aprovacao do owner e reservada a operacoes que mudem estado.',
        ];

        foreach ($this->all() as $role => $contract) {
            $lines[] = strtoupper($role) . ':';
            $lines[] = '- OBJETIVO: ' . $contract['purpose'];
            $lines[] = '- SAIDAS_OBRIGATORIAS: ' . implode(', ', $contract['required_outputs']);
            $lines[] = '- PROIBIDO_ALEGAR: ' . implode(', ', $contract['forbidden_claims']);
            $lines[] = '- PERMISSOES: read=true, analyze=true, recommend=true, write=false, deploy=false, destructive_actions=false';
        }

        return implode("\n", $lines);
    }

    private function contract(string $role, string $purpose, array $requiredOutputs, array $forbiddenClaims): array
    {
        return [
            'version' => self::VERSION,
            'role' => $role,
            'mode' => 'OBSERVER',
            'purpose' => $purpose,
            'required_outputs' => array_values($requiredOutputs),
            'forbidden_claims' => array_values($forbiddenClaims),
            'permissions' => [
                'read' => true,
                'analyze' => true,
                'recommend' => true,
                'write' => false,
                'deploy' => false,
                'destructive_actions' => false,
            ],
            'grounding_policy' => 'strict_v1',
            'owner_authorization_required_for_execution' => true,
        ];
    }
}
