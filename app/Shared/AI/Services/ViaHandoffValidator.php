<?php

declare(strict_types=1);

namespace App\Shared\AI\Services;

use RuntimeException;

class ViaHandoffValidator
{
    public const VERSION = 'handoff_validator_v1';

    public function __construct(private readonly ViaDecisionContracts $decisionContracts)
    {
    }

    public function validate(string $role, array $decision): array
    {
        $contractValidation = $this->decisionContracts->validateDecision($role, $decision);
        $errors = (array) ($contractValidation['errors'] ?? []);
        $nextRole = $contractValidation['next_role'] ?? null;
        $state = (string) ($decision['decision_state'] ?? '');

        if (($decision['execution_claimed'] ?? false) === true) {
            $errors[] = 'handoff:execution_claimed_forbidden';
        }

        if ($state === 'requires_owner_authorization' && $role === 'auditor') {
            $ownerItems = (array) ($decision['owner_authorization_items'] ?? []);
            if ($ownerItems === []) {
                $errors[] = 'handoff:missing_owner_authorization_items';
            }
        }

        if ($state === 'needs_evidence' && $role === 'report') {
            $gaps = (array) ($decision['evidence_gaps'] ?? []);
            if ($gaps === []) {
                $errors[] = 'handoff:missing_evidence_gaps';
            }
        }

        if ($state === 'blocked' && $role === 'project_manager') {
            $blockers = (array) ($decision['blockers'] ?? []);
            if ($blockers === []) {
                $errors[] = 'handoff:missing_blockers';
            }
        }

        $errors = array_values(array_unique($errors));

        return [
            'version' => self::VERSION,
            'role' => $role,
            'next_role' => $nextRole,
            'decision_state' => $state,
            'handoff_allowed' => $errors === [] && $state === 'ready',
            'requires_owner_authorization' => $errors === [] && $state === 'requires_owner_authorization',
            'blocked' => $errors !== [] || in_array($state, ['blocked', 'needs_evidence'], true),
            'valid' => $errors === [],
            'errors' => $errors,
        ];
    }

    public function validateChain(array $decisions): array
    {
        $expectedRoles = ['orchestrator', 'project_manager', 'architect', 'qa', 'auditor', 'report'];
        $results = [];
        $chainErrors = [];

        foreach ($expectedRoles as $index => $role) {
            if (! array_key_exists($role, $decisions)) {
                $chainErrors[] = "missing_role:{$role}";
                continue;
            }

            $result = $this->validate($role, (array) $decisions[$role]);
            $results[$role] = $result;

            if (! $result['valid']) {
                $chainErrors[] = "invalid_role:{$role}";
                continue;
            }

            if ($role !== 'report' && ! $result['handoff_allowed']) {
                $chainErrors[] = "handoff_not_ready:{$role}";
            }

            $expectedNext = $expectedRoles[$index + 1] ?? null;
            if (($result['next_role'] ?? null) !== $expectedNext) {
                $chainErrors[] = "next_role_mismatch:{$role}";
            }
        }

        $contractValid = $chainErrors === [] || collect($chainErrors)->every(
            fn (string $error): bool => str_starts_with($error, 'handoff_not_ready:')
        );
        $executionReady = $chainErrors === [];

        return [
            'version' => self::VERSION,
            'valid' => $contractValid,
            'execution_ready' => $executionReady,
            'roles' => count($results),
            'results' => $results,
            'errors' => array_values(array_unique($chainErrors)),
            'execution_permissions' => [
                'write' => false,
                'deploy' => false,
                'destructive_actions' => false,
            ],
        ];
    }

    public function assertHandoffAllowed(string $role, array $decision): array
    {
        $result = $this->validate($role, $decision);

        if (! $result['handoff_allowed']) {
            throw new RuntimeException('Handoff VIA bloqueado para o papel ' . $role . ': ' . implode(', ', $result['errors']));
        }

        return $result;
    }
}
