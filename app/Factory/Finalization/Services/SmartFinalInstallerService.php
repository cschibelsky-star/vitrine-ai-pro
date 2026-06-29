<?php

declare(strict_types=1);

namespace App\Factory\Finalization\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use RuntimeException;

class SmartFinalInstallerService
{
    public function install(string $blueprintSlug, bool $dryRun = true, bool $force = false, bool $migrate = false): array
    {
        $blueprintPath = storage_path('app/factory/blueprints/' . $blueprintSlug . '.json');

        if (! File::exists($blueprintPath)) {
            throw new RuntimeException("Blueprint não encontrado: {$blueprintSlug}");
        }

        $blueprint = json_decode((string) File::get($blueprintPath), true);
        $modules = array_map(fn ($module) => $module['slug'], $blueprint['modules'] ?? []);

        $base = storage_path('app/factory/finalization/installations/' . date('Ymd_His') . '_' . $blueprintSlug);
        File::ensureDirectoryExists($base);

        $results = [];

        foreach ($modules as $module) {
            $command = 'factory:install-module ' . $module;

            if ($dryRun) {
                $command .= ' --dry-run';
            }

            if ($force) {
                $command .= ' --force';
            }

            $exitCode = Artisan::call($command);

            $results[] = [
                'module' => $module,
                'command' => $command,
                'exit_code' => $exitCode,
                'status' => $exitCode === 0 ? 'passed' : 'failed',
                'output' => Artisan::output(),
            ];
        }

        $migrateResult = null;

        if (! $dryRun && $migrate) {
            $exitCode = Artisan::call('migrate', ['--force' => true]);
            $migrateResult = [
                'command' => 'migrate --force',
                'exit_code' => $exitCode,
                'status' => $exitCode === 0 ? 'passed' : 'failed',
                'output' => Artisan::output(),
            ];
        }

        $status = collect($results)->contains(fn ($item) => $item['status'] === 'failed') ? 'failed' : 'passed';

        if ($migrateResult && $migrateResult['status'] === 'failed') {
            $status = 'failed';
        }

        $report = [
            'blueprint' => $blueprintSlug,
            'mode' => $dryRun ? 'dry_run' : 'install',
            'force' => $force,
            'migrate' => $migrate,
            'status' => $status,
            'modules' => $modules,
            'results' => $results,
            'migrate_result' => $migrateResult,
            'created_at' => now()->toISOString(),
        ];

        $path = $base . '/install_final_report.json';
        File::put($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $report['path'] = $path;

        return $report;
    }
}
