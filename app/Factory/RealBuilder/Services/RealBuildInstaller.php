<?php

declare(strict_types=1);

namespace App\Factory\RealBuilder\Services;

use Illuminate\Support\Facades\File;
use RuntimeException;

class RealBuildInstaller
{
    public function install(string $blueprintSlug, bool $dryRun = true, bool $force = false): array
    {
        $source = storage_path('app/factory/real-builds/' . $blueprintSlug);

        if (! File::isDirectory($source)) {
            throw new RuntimeException("Real build não encontrado: {$blueprintSlug}. Rode factory:real-build {$blueprintSlug} antes.");
        }

        $files = File::allFiles($source);
        $results = [];
        $transactionId = date('Ymd_His') . '_' . bin2hex(random_bytes(4));
        $backupBase = $dryRun
            ? null
            : storage_path('app/factory/backups/real-install/' . $transactionId . '_' . $blueprintSlug);

        foreach ($files as $file) {
            $relative = $file->getRelativePathname();

            if (str_ends_with($relative, 'REAL_BUILD_REPORT.json')) {
                continue;
            }

            $destination = base_path($relative);
            $exists = File::exists($destination);
            $sourceChecksum = hash_file('sha256', $file->getPathname()) ?: null;
            $previousChecksum = $exists ? (hash_file('sha256', $destination) ?: null) : null;
            $backupPath = null;
            $status = 'ready';

            if ($dryRun) {
                $status = $exists
                    ? ($force ? 'would_overwrite_with_backup' : 'would_skip_exists')
                    : 'would_copy';
            } elseif ($exists && ! $force) {
                $status = 'skipped_exists';
            } else {
                if ($exists) {
                    $backupPath = $backupBase . '/' . $relative;
                    File::ensureDirectoryExists(dirname($backupPath));

                    if (! File::copy($destination, $backupPath)) {
                        throw new RuntimeException("Falha ao criar backup antes de sobrescrever: {$destination}");
                    }
                }

                File::ensureDirectoryExists(dirname($destination));

                if (! File::copy($file->getPathname(), $destination)) {
                    throw new RuntimeException("Falha ao instalar arquivo: {$destination}");
                }

                $status = $exists ? 'overwritten_with_backup' : 'copied';
            }

            $installedChecksum = (! $dryRun && File::exists($destination))
                ? (hash_file('sha256', $destination) ?: null)
                : null;

            $results[] = [
                'relative_path' => $relative,
                'source' => $file->getPathname(),
                'destination' => $destination,
                'existed_before' => $exists,
                'status' => $status,
                'source_sha256' => $sourceChecksum,
                'previous_sha256' => $previousChecksum,
                'installed_sha256' => $installedChecksum,
                'backup_path' => $backupPath,
                'rollback_action' => $dryRun
                    ? 'none'
                    : ($exists && $force ? 'restore_backup' : (! $exists ? 'delete_created_file' : 'none')),
            ];
        }

        $summary = [
            'transaction_id' => $transactionId,
            'blueprint' => $blueprintSlug,
            'mode' => $dryRun ? 'dry_run' : 'install',
            'force' => $force,
            'files' => count($results),
            'copied' => count(array_filter($results, fn (array $item) => $item['status'] === 'copied')),
            'overwritten' => count(array_filter($results, fn (array $item) => $item['status'] === 'overwritten_with_backup')),
            'skipped' => count(array_filter($results, fn (array $item) => in_array($item['status'], ['skipped_exists', 'would_skip_exists'], true))),
            'results' => $results,
            'backup_path' => $backupBase,
            'rollback_ready' => ! $dryRun && count(array_filter($results, fn (array $item) => $item['rollback_action'] !== 'none')) > 0,
            'created_at' => now()->toISOString(),
        ];

        $reportDir = storage_path('app/factory/real-installs/' . $blueprintSlug);
        File::ensureDirectoryExists($reportDir);

        $reportPath = $reportDir . '/REAL_INSTALL_REPORT_' . $transactionId . '.json';
        File::put($reportPath, json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $summary['path'] = $reportPath;

        return $summary;
    }
}
