<?php

declare(strict_types=1);

namespace App\Factory\Quality\Services;

use InvalidArgumentException;

/**
 * Formats Factory integrity results for VIA's existing schema 1.0 observer.
 *
 * This is an INCOMPLETE handoff draft, never an approval. Commit/check/reviewer
 * identity must be independently collected by VIA from trusted GitHub sources.
 */
final class FactoryViaObserverHandoffDraft
{
    public function fromIntegrity(array $integrity, string $repository, string $branch): array
    {
        $runId = $integrity['run_id'] ?? null;
        $blueprint = $integrity['blueprint'] ?? null;
        if (!is_string($runId) || !preg_match(
            '/^[a-f0-9]{8}-[a-f0-9]{4}-[1-8][a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/iD',
            $runId
        ) || !is_string($blueprint) || !preg_match('/^[a-z0-9][a-z0-9_-]*$/D', $blueprint)
            || !preg_match('/^[A-Za-z0-9_.-]+\/[A-Za-z0-9_.-]+$/D', $repository)
            || trim($branch) === '' || preg_match('/[\x00-\x1f]/', $branch)) {
            throw new InvalidArgumentException('Invalid run, blueprint, repository or branch.');
        }

        $integrityState = $integrity['decision'] ?? 'unknown';
        if (!in_array($integrityState, [
            'integrity_verified_review_pending',
            'blocked_integrity_mismatch',
            'blocked_missing_or_unsafe_artifact',
        ], true) || ($integrity['can_advance'] ?? null) !== false) {
            throw new InvalidArgumentException('Untrusted or advancing integrity decision.');
        }

        return [
            'schema_version' => '1.0',
            'task_id' => $runId,
            'repository' => $repository,
            'branch' => $branch,
            'agent_id' => 'factory-real-builder',
            'specialty' => 'artifact_generation',
            'objective' => 'Review blueprint ' . $blueprint . ' run ' . $runId,
            'next_owner' => 'independent-qa',
            'state' => 'awaiting_qa',
            'base_sha' => null,
            'result_sha' => null,
            'changed_files' => [],
            'checks' => [
                [
                    'id' => 'trusted_ci_and_functional_tests',
                    'required' => true,
                    'status' => 'not_run',
                    'command' => '',
                    'evidence' => '',
                ],
            ],
            'review' => [
                'status' => 'pending',
                'reviewer_agent_id' => null,
                'github_login' => null,
                'evidence' => null,
            ],
            'authorization' => [
                'approved_for_branch_write' => false,
                'production_approved' => false,
                'scope' => 'observer-draft-only',
            ],
            'risks' => [
                'GitHub commit and CI have not been authenticated',
                'Independent functional/security QA has not been completed',
                'VIA observer is not authorized to advance stages or deploy',
            ],
            'factory_artifact' => [
                'run_id' => $runId,
                'blueprint' => $blueprint,
                'integrity_decision' => $integrityState,
                'independent_integrity_checked' => $integrityState === 'integrity_verified_review_pending',
            ],
            'can_advance' => false,
        ];
    }
}
