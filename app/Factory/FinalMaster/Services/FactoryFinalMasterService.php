<?php

declare(strict_types=1);

namespace App\Factory\FinalMaster\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class FactoryFinalMasterService
{
    public function buildAndInstall(string $request, bool $dryRun = true, bool $force = false, bool $migrate = false): array
    {
        $base = storage_path('app/factory/final-master/' . date('Ymd_His'));
        $stepsDir = $base . '/steps';
        File::ensureDirectoryExists($stepsDir);

        $status = 'finished';
        $steps = [];

        $finalize = $this->call('factory:finalize-request', ['request' => [$request]]);
        $steps['01_finalize_request'] = $this->writeStep($stepsDir, '01_finalize_request', $finalize);

        if ($finalize['status'] !== 'passed') {
            $status = 'failed';
        }

        $blueprint = $this->detectLatestBlueprint();

        $steps['02_detect_blueprint'] = $this->writeStep($stepsDir, '02_detect_blueprint', [
            'blueprint' => $blueprint,
        ]);

        if (! $blueprint) {
            $status = 'failed';
        }

        if ($blueprint) {
            $realBuild = $this->call('factory:real-build', ['blueprint' => $blueprint]);
            $steps['03_real_build'] = $this->writeStep($stepsDir, '03_real_build', $realBuild);

            if ($realBuild['status'] !== 'passed') {
                $status = 'failed';
            }

            $enterpriseBuild = $this->call('factory:enterprise-build', ['blueprint' => $blueprint]);
            $steps['04_enterprise_build'] = $this->writeStep($stepsDir, '04_enterprise_build', $enterpriseBuild);

            if ($enterpriseBuild['status'] !== 'passed') {
                $status = 'failed';
            }

            $realInstallArgs = ['blueprint' => $blueprint];
            $enterpriseInstallArgs = ['blueprint' => $blueprint];

            if ($dryRun) {
                $realInstallArgs['--dry-run'] = true;
                $enterpriseInstallArgs['--dry-run'] = true;
            }

            if ($force) {
                $realInstallArgs['--force'] = true;
                $enterpriseInstallArgs['--force'] = true;
            }

            $realInstall = $this->call('factory:real-install', $realInstallArgs);
            $steps['05_real_install'] = $this->writeStep($stepsDir, '05_real_install', $realInstall);

            if ($realInstall['status'] !== 'passed') {
                $status = 'failed';
            }

            $enterpriseInstall = $this->call('factory:enterprise-install', $enterpriseInstallArgs);
            $steps['06_enterprise_install'] = $this->writeStep($stepsDir, '06_enterprise_install', $enterpriseInstall);

            if ($enterpriseInstall['status'] !== 'passed') {
                $status = 'failed';
            }

            if (! $dryRun) {
                $dump = $this->shell('composer dump-autoload');
                $steps['07_composer_dump_autoload'] = $this->writeStep($stepsDir, '07_composer_dump_autoload', $dump);

                $clear = $this->call('optimize:clear');
                $steps['08_optimize_clear'] = $this->writeStep($stepsDir, '08_optimize_clear', $clear);

                if ($migrate) {
                    $migration = $this->call('migrate', ['--force' => true]);
                    $steps['09_migrate'] = $this->writeStep($stepsDir, '09_migrate', $migration);

                    if ($migration['status'] !== 'passed') {
                        $status = 'failed';
                    }
                }
            }
        }

        $report = [
            'request' => $request,
            'status' => $status,
            'mode' => $dryRun ? 'dry_run' : 'install',
            'force' => $force,
            'migrate' => $migrate,
            'blueprint' => $blueprint,
            'steps' => $steps,
            'final_note' => $dryRun
                ? 'Dry-run concluído. Para instalar de verdade, rode o mesmo comando com --force --migrate.'
                : 'Instalação executada. Verifique painel, migrations e logs.',
            'created_at' => now()->toISOString(),
        ];

        $path = $base . '/FINAL_MASTER_REPORT.json';
        File::put($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $report['path'] = $path;

        return $report;
    }

    protected function call(string $command, array $args = []): array
    {
        $exitCode = Artisan::call($command, $args);

        return [
            'command' => $command,
            'arguments' => $args,
            'exit_code' => $exitCode,
            'status' => $exitCode === 0 ? 'passed' : 'failed',
            'output' => Artisan::output(),
        ];
    }

    protected function shell(string $command): array
    {
        $output = [];
        $exitCode = 0;

        exec($command . ' 2>&1', $output, $exitCode);

        return [
            'command' => $command,
            'exit_code' => $exitCode,
            'status' => $exitCode === 0 ? 'passed' : 'failed',
            'output' => implode("\n", $output),
        ];
    }

    protected function detectLatestBlueprint(): ?string
    {
        $dir = storage_path('app/factory/finalization/productions');

        if (! File::isDirectory($dir)) {
            return null;
        }

        $file = collect(File::allFiles($dir))
            ->filter(fn ($item) => $item->getFilename() === 'finalization_report.json')
            ->sortByDesc(fn ($item) => $item->getMTime())
            ->first();

        if (! $file) {
            return null;
        }

        $report = json_decode((string) File::get($file->getPathname()), true);

        return $report['blueprint_slug'] ?? null;
    }

    protected function writeStep(string $dir, string $name, array $payload): string
    {
        $path = $dir . '/' . $name . '.json';
        File::put($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $path;
    }
}
