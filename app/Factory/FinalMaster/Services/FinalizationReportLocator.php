<?php

declare(strict_types=1);

namespace App\Factory\FinalMaster\Services;

use RuntimeException;

/**
 * Resolves only the report explicitly returned by the current finalization
 * command. Never picks a report from another mission by global mtime.
 */
final class FinalizationReportLocator
{
    public function resolve(string $commandOutput, string $expectedRequest, string $reportsRoot): array
    {
        if (!preg_match_all('/^FACTORY_FINALIZATION_REPORT_PATH=(.+)$/m', $commandOutput, $matches)
            || count($matches[1]) !== 1) {
            throw new RuntimeException('Finalization did not return exactly one report path.');
        }

        $root = realpath($reportsRoot);
        $path = realpath(trim($matches[1][0]));
        if ($root === false || $path === false
            || !str_starts_with($path, $root . DIRECTORY_SEPARATOR)
            || basename($path) !== 'finalization_report.json'
            || !is_file($path)) {
            throw new RuntimeException('Finalization report path is invalid or outside the allowed directory.');
        }

        $report = json_decode((string) file_get_contents($path), true);
        if (!is_array($report)
            || ($report['status'] ?? null) !== 'finished'
            || ($report['request'] ?? null) !== $expectedRequest
            || !is_string($report['blueprint_slug'] ?? null)
            || !preg_match('/^[a-z0-9][a-z0-9_-]*$/D', $report['blueprint_slug'])) {
            throw new RuntimeException('Finalization report does not match this request or is incomplete.');
        }

        return ['blueprint' => $report['blueprint_slug'], 'report_path' => $path];
    }
}
