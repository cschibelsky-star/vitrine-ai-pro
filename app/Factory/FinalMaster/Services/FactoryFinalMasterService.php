<?php

declare(strict_types=1);

namespace App\Factory\FinalMaster\Services;

use App\Factory\Finalization\Services\FinalizationProductionService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class FactoryFinalMasterService
{
    public function __construct(
        protected FinalizationProductionService $finalizationProduction,
    ) {
    }

    public function buildAndInstall(string $request, bool $dryRun = true, bool $force = false, bool $migrate = false): array
    {
        if (! $dryRun) {
            return $this->execute($request, false, $force, $migrate);
        }

        $originalStoragePath = storage_path();
        $reportBase = $originalStoragePath
            . '/app/factory/final-master/'
            . date('Ymd_His')
            . '_'
            . bin2hex(random_bytes(4));
        $sandboxStoragePath = $reportBase . '/sandbox-storage';

        File::ensureDirectoryExists($sandboxStoragePath);
        app()->useStoragePath($sandboxStoragePath);

        try {
            return $this->execute(
                $request,
                true,
                $force,
                false,
                $reportBase,
                $sandboxStoragePath,
            );
        } finally {
            app()->useStoragePath($originalStoragePath);
        }
    }

    protected function execute(
        string $request,
        bool $dryRun,
        bool $force,
        bool $migrate,
        ?string $reportBase = null,
        ?string $sandboxStoragePath = null,
    ): array {
        $startedAt = microtime(true);
        $base = $reportBase
            ?? storage_path(
                'app/factory/final-master/'
                . date('Ymd_His')
                . '_'
                . bin2hex(random_bytes(4))
            );
        $stepsDir = $base . '/steps';
        File::ensureDirectoryExists($stepsDir);

        $steps = [];

        try {
            $finalization = $this->finalizationProduction->produce($request);
        } catch (\Throwable $exception) {
            $steps['01_finalize_request'] = $this->writeStep($stepsDir, '01_finalize_request', [
                'status' => 'failed',
                'error' => $exception->getMessage(),
            ]);

            return $this->failedReport(
                base: $base,
                request: $request,
                dryRun: $dryRun,
                force: $force,
                migrate: $migrate,
                steps: $steps,
                failedStage: 'finalize-request',
                error: $exception->getMessage(),
                startedAt: $startedAt,
                sandboxStoragePath: $sandboxStoragePath,
            );
        }

        $steps['01_finalize_request'] = $this->writeStep($stepsDir, '01_finalize_request', $finalization);

        if (($finalization['status'] ?? 'failed') !== 'finished') {
            return $this->failedReport(
                base: $base,
                request: $request,
                dryRun: $dryRun,
                force: $force,
                migrate: $migrate,
                steps: $steps,
                failedStage: 'finalize-request',
                error: 'A finalização da solicitação não foi concluída com sucesso.',
                startedAt: $startedAt,
                sandboxStoragePath: $sandboxStoragePath,
                finalization: $finalization,
            );
        }

        $blueprint = $finalization['blueprint_slug'] ?? null;

        $steps['02_blueprint_context'] = $this->writeStep($stepsDir, '02_blueprint_context', [
            'blueprint' => $blueprint,
            'blueprint_path' => $finalization['blueprint_path'] ?? null,
            'production_path' => $finalization['production_path'] ?? null,
            'finalization_report' => $finalization['path'] ?? null,
        ]);

        if (! is_string($blueprint) || $blueprint === '') {
            return $this->failedReport(
                base: $base,
                request: $request,
                dryRun: $dryRun,
                force: $force,
                migrate: $migrate,
                steps: $steps,
                failedStage: 'blueprint-context',
                error: 'A produção não retornou um blueprint_slug válido.',
                startedAt: $startedAt,
                sandboxStoragePath: $sandboxStoragePath,
                finalization: $finalization,
            );
        }

        $realBuild = $this->call('factory:real-build', ['blueprint' => $blueprint]);
        $steps['03_real_build'] = $this->writeStep($stepsDir, '03_real_build', $realBuild);

        if ($realBuild['status'] !== 'passed') {
            return $this->failedReport(
                base: $base,
                request: $request,
                dryRun: $dryRun,
                force: $force,
                migrate: $migrate,
                steps: $steps,
                failedStage: 'real-build',
                error: $realBuild['output'] ?: 'Real Build falhou.',
                startedAt: $startedAt,
                sandboxStoragePath: $sandboxStoragePath,
                finalization: $finalization,
            );
        }

        $enterpriseBuild = $this->call('factory:enterprise-build', ['blueprint' => $blueprint]);
        $steps['04_enterprise_build'] = $this->writeStep($stepsDir, '04_enterprise_build', $enterpriseBuild);

        if ($enterpriseBuild['status'] !== 'passed') {
            return $this->failedReport(
                base: $base,
                request: $request,
                dryRun: $dryRun,
                force: $force,
                migrate: $migrate,
                steps: $steps,
                failedStage: 'enterprise-build',
                error: $enterpriseBuild['output'] ?: 'Enterprise Build falhou.',
                startedAt: $startedAt,
                sandboxStoragePath: $sandboxStoragePath,
                finalization: $finalization,
            );
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
            return $this->failedReport(
                base: $base,
                request: $request,
                dryRun: $dryRun,
                force: $force,
                migrate: $migrate,
                steps: $steps,
                failedStage: 'real-install',
                error: $realInstall['output'] ?: 'Real Install falhou.',
                startedAt: $startedAt,
                sandboxStoragePath: $sandboxStoragePath,
                finalization: $finalization,
            );
        }

        $enterpriseInstall = $this->call('factory:enterprise-install', $enterpriseInstallArgs);
        $steps['06_enterprise_install'] = $this->writeStep($stepsDir, '06_enterprise_install', $enterpriseInstall);

        if ($enterpriseInstall['status'] !== 'passed') {
            return $this->failedReport(
                base: $base,
                request: $request,
                dryRun: $dryRun,
                force: $force,
                migrate: $migrate,
                steps: $steps,
                failedStage: 'enterprise-install',
                error: $enterpriseInstall['output'] ?: 'Enterprise Install falhou.',
                startedAt: $startedAt,
                sandboxStoragePath: $sandboxStoragePath,
                finalization: $finalization,
            );
        }

        if (! $dryRun) {
            $dump = $this->shell('composer dump-autoload');
            $steps['07_composer_dump_autoload'] = $this->writeStep($stepsDir, '07_composer_dump_autoload', $dump);

            if ($dump['status'] !== 'passed') {
                return $this->failedReport(
                    base: $base,
                    request: $request,
                    dryRun: $dryRun,
                    force: $force,
                    migrate: $migrate,
                    steps: $steps,
                    failedStage: 'composer-dump-autoload',
                    error: $dump['output'] ?: 'composer dump-autoload falhou.',
                    startedAt: $startedAt,
                    sandboxStoragePath: $sandboxStoragePath,
                    finalization: $finalization,
                );
            }

            $clear = $this->call('optimize:clear');
            $steps['08_optimize_clear'] = $this->writeStep($stepsDir, '08_optimize_clear', $clear);

            if ($clear['status'] !== 'passed') {
                return $this->failedReport(
                    base: $base,
                    request: $request,
                    dryRun: $dryRun,
                    force: $force,
                    migrate: $migrate,
                    steps: $steps,
                    failedStage: 'optimize-clear',
                    error: $clear['output'] ?: 'optimize:clear falhou.',
                    startedAt: $startedAt,
                    sandboxStoragePath: $sandboxStoragePath,
                    finalization: $finalization,
                );
            }

            if ($migrate) {
                $migration = $this->call('migrate', ['--force' => true]);
                $steps['09_migrate'] = $this->writeStep($stepsDir, '09_migrate', $migration);

                if ($migration['status'] !== 'passed') {
                    return $this->failedReport(
                        base: $base,
                        request: $request,
                        dryRun: $dryRun,
                        force: $force,
                        migrate: $migrate,
                        steps: $steps,
                        failedStage: 'migrate',
                        error: $migration['output'] ?: 'Migration falhou.',
                        startedAt: $startedAt,
                        sandboxStoragePath: $sandboxStoragePath,
                        finalization: $finalization,
                    );
                }
            }
        }

        return $this->persistReport($base, [
            'production_id' => basename($base),
            'request' => $request,
            'status' => 'finished',
            'mode' => $dryRun ? 'dry_run' : 'install',
            'force' => $force,
            'migrate' => $migrate,
            'blueprint' => $blueprint,
            'blueprint_path' => $finalization['blueprint_path'] ?? null,
            'production_path' => $finalization['production_path'] ?? null,
            'finalization_report' => $finalization['path'] ?? null,
            'steps' => $steps,
            'sandbox_storage_path' => $sandboxStoragePath,
            'shared_storage_untouched' => $dryRun,
            'duration_seconds' => round(microtime(true) - $startedAt, 3),
            'final_note' => $dryRun
                ? 'Dry-run isolado concluído. Revise o relatório antes de autorizar uma instalação real.'
                : 'Instalação executada. Verifique painel, migrations e logs.',
            'created_at' => now()->toISOString(),
        ]);
    }

    protected function failedReport(
        string $base,
        string $request,
        bool $dryRun,
        bool $force,
        bool $migrate,
        array $steps,
        string $failedStage,
        string $error,
        float $startedAt,
        ?string $sandboxStoragePath = null,
        array $finalization = [],
    ): array {
        return $this->persistReport($base, [
            'production_id' => basename($base),
            'request' => $request,
            'status' => 'failed',
            'failed_stage' => $failedStage,
            'error' => $error,
            'mode' => $dryRun ? 'dry_run' : 'install',
            'force' => $force,
            'migrate' => $migrate,
            'blueprint' => $finalization['blueprint_slug'] ?? null,
            'blueprint_path' => $finalization['blueprint_path'] ?? null,
            'production_path' => $finalization['production_path'] ?? null,
            'finalization_report' => $finalization['path'] ?? null,
            'steps' => $steps,
            'sandbox_storage_path' => $sandboxStoragePath,
            'shared_storage_untouched' => $dryRun,
            'duration_seconds' => round(microtime(true) - $startedAt, 3),
            'final_note' => 'Pipeline interrompido na primeira falha crítica.',
            'created_at' => now()->toISOString(),
        ]);
    }

    protected function call(string $command, array $args = []): array
    {
        $startedAt = microtime(true);
        $exitCode = Artisan::call($command, $args);

        return [
            'command' => $command,
            'arguments' => $args,
            'exit_code' => $exitCode,
            'status' => $exitCode === 0 ? 'passed' : 'failed',
            'output' => Artisan::output(),
            'duration_seconds' => round(microtime(true) - $startedAt, 3),
        ];
    }

    protected function shell(string $command): array
    {
        $startedAt = microtime(true);
        $output = [];
        $exitCode = 0;

        exec($command . ' 2>&1', $output, $exitCode);

        return [
            'command' => $command,
            'exit_code' => $exitCode,
            'status' => $exitCode === 0 ? 'passed' : 'failed',
            'output' => implode("\n", $output),
            'duration_seconds' => round(microtime(true) - $startedAt, 3),
        ];
    }

    protected function writeStep(string $dir, string $name, array $payload): string
    {
        $path = $dir . '/' . $name . '.json';
        File::put($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $path;
    }

    protected function persistReport(string $base, array $report): array
    {
        $path = $base . '/FINAL_MASTER_REPORT.json';
        File::put($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $report['path'] = $path;

        return $report;
    }
}
