<?php

declare(strict_types=1);

namespace App\Factory\RealBuilder\Services;

use RuntimeException;

/**
 * Verifies the immutable identity and current hashes of generated staging
 * files. This is NOT a test, security approval, or deployment authorization.
 */
final class RunArtifactEvidence
{
    public function collect(string $blueprint, string $runId, string $buildRoot): array
    {
        $relative = RealBuildPath::relative($blueprint, $runId);
        $directory = realpath($buildRoot . DIRECTORY_SEPARATOR . $relative);

        if ($directory === false || !is_dir($directory)) {
            throw new RuntimeException('Run staging directory is absent.');
        }

        $reportPath = $directory . DIRECTORY_SEPARATOR . 'REAL_BUILD_REPORT.json';
        if (is_link($reportPath) || !is_file($reportPath)) {
            throw new RuntimeException('Run build report is absent or unsafe.');
        }

        $report = json_decode((string) file_get_contents($reportPath), true);
        if (!is_array($report)
            || ($report['blueprint'] ?? null) !== $blueprint
            || ($report['run_id'] ?? null) !== $runId
            || ($report['status'] ?? null) !== 'generated'
            || !is_array($report['files'] ?? null)
            || ($report['files_count'] ?? null) !== count($report['files'])
            || !$report['files']) {
            throw new RuntimeException('Run build report is incomplete or mismatched.');
        }

        $artifacts = [];
        foreach ($report['files'] as $rawPath) {
            if (!is_string($rawPath) || is_link($rawPath)) {
                throw new RuntimeException('Unsafe artifact path.');
            }
            $file = realpath($rawPath);
            if ($file === false || !is_file($file)
                || !str_starts_with($file, $directory . DIRECTORY_SEPARATOR)) {
                throw new RuntimeException('Artifact is absent or outside the run staging directory.');
            }

            $relativePath = substr($file, strlen($directory) + 1);
            if (isset($artifacts[$relativePath])) {
                throw new RuntimeException('Duplicate artifact in build report.');
            }
            $artifacts[$relativePath] = hash_file('sha256', $file);
        }

        ksort($artifacts);
        return [
            'blueprint' => $blueprint,
            'run_id' => $runId,
            'verification' => 'staging_file_hashes_only',
            'qa_passed' => false,
            'via_approved' => false,
            'homologated' => false,
            'build_report_sha256' => hash_file('sha256', $reportPath),
            'artifacts' => $artifacts,
        ];
    }
}
