<?php

declare(strict_types=1);

namespace App\Shared\AI\Services;

use RuntimeException;

class ViaDecisionContracts
{
    public const VERSION = 'decision_contracts_v1';

    private const ORDER = [
        'orchestrator',
        'project_manager',
        'architect',
        'qa',
        'auditor',
        'report',
    ];

    public function all(): array
    {
        return [
            'orchestrator' => $this->contract(
                role: 'orchestrator',
                nextRole: 'project_manager',
                requiredFields: ['mission_scope', 'critical_dependencies', 'handoff_to_project_manager'],
            ),
            'project_manager' => $this->contract(
                role: 'project_manager',
                nextRole: 'architect',
                requiredFields: ['priority', 'sequence', 'blockers', 'acceptance_criteria'],
            ),
            'architect' => $this->contract(
                role: 'architect',
                nextRole: 'qa',
                requiredFields: ['architecture_findings', 'integration_impact', 'security_impact', 'reversibility'],
            ),
            'qa' => $this->contract(
                role: 'qa',
                nextRole: 'auditor',
                requiredFields: ['validation_plan', 'test_cases', 'expected_results'],
            ),
            'auditor' => $this->contract(
                role: 'auditor',
                nextRole: 'report',
                requiredFields: ['grounding_review', 'risk_review', 'permission_review', 'owner_authorization_items'],
            ),
            'report' => $this->contract(
                role: 'report',
                nextRole: null,
                requiredFields: ['diagnosis', 'evidence', 'evidence_gaps', 'risks', 'recommendations', 'validation_criteria'],
            ),
        ];
    }

    public function get(string $role): array
    {
        $contracts = $this->all();

        if (! isset($contracts[$role])) {
            throw new RuntimeException("Contrato de decisão VIA desconhecido: {$role}");
        }

        return $contracts[$role];
    }

    public function validateDefinition(): array
    {
        $contracts = $this->all();
        $errors = [];

        if (array_keys($contracts) !== self::ORDER) {
            $errors[] = 'role_order_mismatch';
        }

        foreach (self::ORDER as $index => $role) {
            $contract = $contracts[$role] ?? [];
            $expectedNext = self::ORDER[$index + 1] ?? null;

            if (($contract['version'] ?? null) !== self::VERSION) {
                $errors[] = "{$role}:version";
            }
            if (($contract['role'] ?? null) !== $role) {
                $errors[] = "{$role}:role";
            }
            if (($contract['next_role'] ?? null) !== $expectedNext) {
                $errors[] = "{$role}:next_role";
            }
            if (($contract['required_fields'] ?? []) === []) {
                $errors[] = "{$role}:required_fields";
            }
            if (($contract['decision_states'] ?? []) !== ['ready', 'blocked', 'needs_evidence', 'requires_owner_authorization']) {
                $errors[] = "{$role}:decision_states";
            }
            if (($contract['permissions']['write'] ?? true) !== false
                || ($contract['permissions']['deploy'] ?? true) !== false
                || ($contract['permissions']['destructive_actions'] ?? true) !== false) {
                $errors[] = "{$role}:permissions";
            }
        }

        return [
            'version' => self::VERSION,
            'roles' => count($contracts),
            'valid' => $errors === [],
            'errors' => $errors,
        ];
    }

    public function validateDecision(string $role, array $decision): array
    {
        $contract = $this->get($role);
        $errors = [];

        foreach ($contract['required_fields'] as $field) {
            if (! array_key_exists($field, $decision)) {
                $errors[] = "missing:{$field}";
            }
        }

        $state = (string) ($decision['decision_state'] ?? '');
        if (! in_array($state, $contract['decision_states'], true)) {
            $errors[] = 'invalid:decision_state';
        }

        $evidenceRefs = (array) ($decision['evidence_refs'] ?? []);
        if ($evidenceRefs === [] && $state !== 'needs_evidence') {
            $errors[] = 'missing:evidence_refs';
        }

        if (($decision['execution_claimed'] ?? false) === true) {
            $errors[] = 'forbidden:execution_claimed';
        }

        return [
            'version' => self::VERSION,
            'role' => $role,
            'valid' => $errors === [],
            'errors' => $errors,
            'next_role' => $contract['next_role'],
        ];
    }

    public function promptBlock(): string
    {
        $lines = [
            'DECISION_CONTRACTS_V1:',
            'Cada papel deve produzir um bloco de decisão estruturado antes do handoff ao próximo papel.',
            'Campos comuns obrigatórios: decision_state, evidence_refs, execution_claimed=false.',
            'decision_state permitido: ready | blocked | needs_evidence | requires_owner_authorization.',
        ];

        foreach ($this->all() as $role => $contract) {
            $lines[] = strtoupper($role) . ': required_fields=' . implode(',', $contract['required_fields'])
                . '; next_role=' . ($contract['next_role'] ?? 'END');
        }

        return implode("\n", $lines);
    }

    private function contract(string $role, ?string $nextRole, array $requiredFields): array
    {
        return [
            'version' => self::VERSION,
            'role' => $role,
            'next_role' => $nextRole,
            'required_fields' => array_values($requiredFields),
            'common_required_fields' => ['decision_state', 'evidence_refs', 'execution_claimed'],
            'decision_states' => ['ready', 'blocked', 'needs_evidence', 'requires_owner_authorization'],
            'permissions' => [
                'read' => true,
                'analyze' => true,
                'recommend' => true,
                'write' => false,
                'deploy' => false,
                'destructive_actions' => false,
            ],
            'grounding_policy' => 'strict_v1',
        ];
    }
}
