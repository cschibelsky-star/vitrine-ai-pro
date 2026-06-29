<?php

declare(strict_types=1);

namespace App\Factory\EnterpriseMaturity\Services;

use Illuminate\Support\Facades\File;

class EnterpriseBuildInstaller
{
    public function install(string $blueprintSlug, bool $dryRun = true, bool $force = false): array
    {
        $source = storage_path('app/factory/enterprise-builds/' . $blueprintSlug);

        if (! File::isDirectory($source)) {
            throw new \RuntimeException("Enterprise build não encontrado. Rode factory:enterprise-build {$blueprintSlug} antes.");
        }

        $files = File::allFiles($source);
        $results = [];
        $backupBase = storage_path('app/factory/backups/enterprise-install/' . date('Ymd_His') . '_' . $blueprintSlug);

        foreach ($files as $file) {
            $relative = $file->getRelativePathname();

            if (str_ends_with($relative, 'ENTERPRISE_BUILD_REPORT.json')) {
                continue;
            }

            $destination = base_path($relative);
            $exists = File::exists($destination);

            if ($dryRun) {
                $status = $exists ? 'would_skip_or_overwrite_with_force' : 'would_copy';
            } elseif ($exists && ! $force) {
                $status = 'skipped_exists';
            } else {
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
                'status' => $status,
            ];
        }

        $report = [
            'blueprint' => $blueprintSlug,
            'mode' => $dryRun ? 'dry_run' : 'install',
            'force' => $force,
            'files' => count($results),
            'results' => $results,
            'backup_path' => $backupBase,
            'created_at' => now()->toISOString(),
        ];

        $reportDir = storage_path('app/factory/enterprise-installs/' . $blueprintSlug);
        File::ensureDirectoryExists($reportDir);

        $path = $reportDir . '/ENTERPRISE_INSTALL_REPORT_' . date('Ymd_His') . '.json';
        File::put($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $report['path'] = $path;

        return $report;
    }
}
