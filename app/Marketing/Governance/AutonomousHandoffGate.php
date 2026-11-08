<?php

declare(strict_types=1);

namespace App\Marketing\Governance;

/**
 * Local structural gate for engineering handoffs.
 * This class DOES NOT authenticate external evidence or authorize progression.
 */
final class AutonomousHandoffGate
{
    /** @param array<string, mixed> $payload
     *  @return array{decision:string,can_advance:bool,evidence_verified:bool,failures:list<string>}
     */
    public function evaluate(array $payload): array
    {
        $failures = [];
        foreach (['task_id', 'repository', 'branch', 'agent_id', 'specialty', 'objective', 'next_owner'] as $field) {
            if (! $this->filled($payload[$field] ?? null)) {
                $failures[] = "missing {$field}";
            }
        }

        if (($payload['schema_version'] ?? null) !== '1.0') {
            $failures[] = 'unsupported schema_version';
        }

        if (($payload['state'] ?? null) !== 'ready_for_via') {
            $failures[] = 'state must be ready_for_via';
        }

        if (in_array($payload['branch'] ?? '', ['main', 'master', 'production', 'prod'], true)) {
            $failures[] = 'target branch must be isolated';
        }

        $authorization = $payload['authorization'] ?? null;
        if (! is_array($authorization)
            || ! $this->filled($authorization['scope'] ?? null)
            || ($authorization['approved_for_branch_write'] ?? null) !== true
            || ($authorization['production_approved'] ?? null) !== false) {
            $failures[] = 'branch authorization invalid';
        }

        $base = $payload['base_sha'] ?? null;
        $result = $payload['result_sha'] ?? null;
        if (! $this->sha($base) || ! $this->sha($result) || $base === $result) {
            $failures[] = 'commit SHAs invalid or unchanged';
        }

        $changed = $payload['changed_files'] ?? null;
        if (! is_array($changed) || $changed === [] || array_filter($changed, fn ($p) => ! $this->filled($p)) !== []) {
            $failures[] = 'changed_files invalid';
        }

        $checks = $payload['checks'] ?? null;
        if (! is_array($checks) || $checks === []) {
            $failures[] = 'checks missing';
        } else {
            $required = 0;
            foreach ($checks as $index => $check) {
                if (! is_array($check)
                    || ! $this->filled($check['id'] ?? null)
                    || ! is_bool($check['required'] ?? null)
                    || ! in_array($check['status'] ?? null, ['passed', 'failed', 'not_run'], true)) {
                    $failures[] = "check {$index} malformed";
                    continue;
                }
                if ($check['required'] === true) {
                    $required++;
                    if ($check['status'] !== 'passed'
                        || ! $this->filled($check['command'] ?? null)
                        || ! $this->filled($check['evidence'] ?? null)) {
                        $failures[] = "required check {$index} not proven passed";
                    }
                }
            }
            if ($required === 0) {
                $failures[] = 'no required checks';
            }
        }

        $review = $payload['review'] ?? null;
        if (! is_array($review)
            || ($review['status'] ?? null) !== 'approved'
            || ! $this->filled($review['reviewer_agent_id'] ?? null)
            || ! $this->filled($review['evidence'] ?? null)
            || ($review['reviewer_agent_id'] ?? null) === ($payload['agent_id'] ?? null)) {
            $failures[] = 'independent review missing or invalid';
        }

        if (! is_array($payload['risks'] ?? null)) {
            $failures[] = 'risks missing';
        }

        return [
            'decision' => $failures === [] ? 'pending_external_verification' : 'blocked',
            'can_advance' => false,
            'evidence_verified' => false,
            'failures' => $failures,
        ];
    }

    private function filled(mixed $value): bool
    {
        return is_string($value) && trim($value) !== '';
    }

    private function sha(mixed $value): bool
    {
        return is_string($value) && preg_match('/^[a-f0-9]{40}$/i', $value) === 1;
    }
}
