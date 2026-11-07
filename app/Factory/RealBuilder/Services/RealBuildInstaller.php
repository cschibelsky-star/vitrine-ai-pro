<?php

declare(strict_types=1);

namespace App\Factory\RealBuilder\Services;

use Illuminate\Support\Facades\File;

class RealBuildInstaller
{
    public function install(string $blueprintSlug, bool $dryRun = true, bool $force = false, ?string $runId = null): array
    {
        $relativeBuild = RealBuildPath::relative($blueprintSlug, $runId);
        $source = storage_path('app/factory/real-builds/' . $relativeBuild);

        if (! File::isDirectory($source)) {
            throw new \RuntimeException("Real build não encontrado: {$blueprintSlug}. Rode factory:real-build {$blueprintSlug} antes.");
        }

        $files = array_values(array_filter(File::allFiles($source),
            fn ($file) => $runId !== null || !str_starts_with($file->getRelativePathname(), 'runs' . DIRECTORY_SEPARATOR)
        ));
        $results = [];

        if ($runId !== null) {
            foreach ($files as $candidate) {
                if (is_link($candidate->getPathname()) || !str_starts_with(
                    realpath($candidate->getPathname()) ?: '',
                    (realpath($source) ?: '') . DIRECTORY_SEPARATOR
                )) {
                    throw new \RuntimeException('Unsafe staged file path.');
                }
            }
        }
        $backupBase = storage_path('app/factory/backups/real-install/' . date('Ymd_His') . '_' . $blueprintSlug . ($runId ? '_' . $runId : ''));

        foreach ($files as $file) {
            $relative = $file->getRelativePathname();

            if (str_ends_with($relative, 'REAL_BUILD_REPORT.json')) {
                continue;
            }

            $destination = base_path($relative);
            $exists = File::exists($destination);
            $status = 'ready';

            if ($exists && ! $force) {
                $status = 'skipped_exists';
            } elseif (! $dryRun) {
                if ($exists) {
                    $backupPath = $backupBase . '/' . $relative;
                    File::ensureDirectoryExists(dirname($backupPath));
                    File::copy($destination, $backupPath);
                }

                File::ensureDirectoryExists(dirname($destination));
                File::copy($file->getPathname(), $destination);
                $status = $exists ? 'overwritten_with_backup' : 'copied';
            }

            $results[] = [
                'source' => $file->getPathname(),
                'destination' => $destination,
                'exists' => $exists,
                'status' => $dryRun ? ($exists ? 'would_skip_or_overwrite_with_force' : 'would_copy') : $status,
            ];
        }

        $summary = [
            'blueprint' => $blueprintSlug,
            'run_id' => $runId,
            'mode' => $dryRun ? 'dry_run' : 'install',
            'force' => $force,
            'files' => count($results),
            'results' => $results,
            'backup_path' => $backupBase,
            'created_at' => now()->toISOString(),
        ];

        $reportDir = storage_path('app/factory/real-installs/' . $relativeBuild);
        File::ensureDirectoryExists($reportDir);

        $reportPath = $reportDir . '/REAL_INSTALL_REPORT_' . date('Ymd_His') . '_' . \Illuminate\Support\Str::uuid() . '.json';
        File::put($reportPath, json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $summary['path'] = $reportPath;

        return $summary;
    }
}
