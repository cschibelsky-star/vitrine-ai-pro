<?php

declare(strict_types=1);

namespace App\Factory\Quality\Services;

use App\Factory\RealBuilder\Services\RunArtifactEvidence;
use InvalidArgumentException;
use RuntimeException;

/**
 * Separate read-only integrity gate. It does not trust stored pass flags and
 * never grants QA/VIA approval, merge, deployment or homologation authority.
 */
final class IndependentArtifactIntegrityGate
{
    public function __construct(private readonly RunArtifactEvidence $collector)
    {
    }

    public function verify(array $expected, string $buildRoot): array
    {
        $blueprint = $expected['blueprint'] ?? null;
        $runId = $expected['run_id'] ?? null;
        if (!is_string($blueprint) || !is_string($runId)) {
            throw new InvalidArgumentException('Blueprint and run ID are required.');
        }

        try {
            $current = $this->collector->collect($blueprint, $runId, $buildRoot);
        } catch (RuntimeException $exception) {
            return $this->decision($blueprint, $runId, 'blocked_missing_or_unsafe_artifact');
        }
        $expectedFiles = $expected['artifacts'] ?? null;
        if (!is_array($expectedFiles)
            || !$expectedFiles
            || ($expected['verification'] ?? null) !== 'staging_file_hashes_only'
            || !is_string($expected['build_report_sha256'] ?? null)
            || !hash_equals($expected['build_report_sha256'], $current['build_report_sha256'])
            || count($expectedFiles) !== count($current['artifacts'])) {
            return $this->decision($blueprint, $runId, 'blocked_integrity_mismatch');
        }

        foreach ($current['artifacts'] as $path => $hash) {
            if (!isset($expectedFiles[$path])
                || !is_string($expectedFiles[$path])
                || !hash_equals($hash, $expectedFiles[$path])) {
                return $this->decision($blueprint, $runId, 'blocked_integrity_mismatch');
            }
        }

        // Integrity is not functional QA. Independent CI, security review,
        // trusted provenance, and VIA checks are separate, mandatory gates.
        return $this->decision($blueprint, $runId, 'integrity_verified_review_pending');
    }

    private function decision(string $blueprint, string $runId, string $state): array
    {
        return [
            'blueprint' => $blueprint,
            'run_id' => $runId,
            'decision' => $state,
            'can_advance' => false,
            'qa_passed' => false,
            'via_approved' => false,
            'homologated' => false,
            'required_next_evidence' => [
                'trusted_git_commit_and_ci',
                'functional_and_security_tests',
                'independent_reviewer',
                'via_observer_review',
                'human_homologation_authorization',
            ],
        ];
    }
}
