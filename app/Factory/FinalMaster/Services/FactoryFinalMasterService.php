<?php

declare(strict_types=1);

namespace App\Factory\FinalMaster\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class FactoryFinalMasterService
{
    public function buildAndInstall(
        string $request,
        bool $dryRun = true,
        bool $force = false,
        bool $migrate = false,
        ?string $idempotencyKey = null,
    ): array {
        $fingerprint = hash('sha256', trim($request) . '|' . ($dryRun ? 'dry' : 'install'));
        $idempotencyPath = storage_path('app/factory/final-master/idempotency/' . hash('sha256', $fingerprint . '|' . (string) $idempotencyKey) . '.json');

        if ($idempotencyKey && File::exists($idempotencyPath)) {
            return array_merge(json_decode((string) File::get($idempotencyPath), true), ['replayed' => true]);
        }

        $lock = Cache::lock('factory:final-master:' . $fingerprint, max(60, (int) config('factory_operational.final_master_lock_seconds', 7200)));

        if (! $lock->get()) {
            throw new RuntimeException('Já existe uma execução equivalente do Final Master em andamento.');
        }

        try {
        $runId = (string) Str::uuid();
        $startedAt = now();
        $base = storage_path('app/factory/final-master/' . $runId);
        $stepsDir = $base . '/steps';
        File::ensureDirectoryExists($stepsDir);

        $status = 'finished';
        $steps = [];

        try {
            $finalize = $this->call('factory:finalize-request', ['request' => [$request]]);
            $steps['01_finalize_request'] = $this->writeStep($stepsDir, '01_finalize_request', $finalize);

            if ($finalize['status'] !== 'passed') {
                throw new RuntimeException('A finalização da solicitação falhou; build e instalação foram interrompidos.');
            }

            $blueprint = $this->detectLatestBlueprint($startedAt->getTimestamp());
            $steps['02_detect_blueprint'] = $this->writeStep($stepsDir, '02_detect_blueprint', ['blueprint' => $blueprint]);

            if (! $blueprint) {
                throw new RuntimeException('Nenhum blueprint novo foi produzido por esta execução.');
            }

            $realBuild = $this->requiredCall('factory:real-build', ['blueprint' => $blueprint]);
            $steps['03_real_build'] = $this->writeStep($stepsDir, '03_real_build', $realBuild);

            $enterpriseBuild = $this->requiredCall('factory:enterprise-build', ['blueprint' => $blueprint]);
            $steps['04_enterprise_build'] = $this->writeStep($stepsDir, '04_enterprise_build', $enterpriseBuild);

            $realInstallArgs = ['blueprint' => $blueprint, '--dry-run' => $dryRun, '--force' => $force];
            $enterpriseInstallArgs = ['blueprint' => $blueprint, '--dry-run' => $dryRun, '--force' => $force];

            $realInstall = $this->requiredCall('factory:real-install', $realInstallArgs);
            $steps['05_real_install'] = $this->writeStep($stepsDir, '05_real_install', $realInstall);

            $enterpriseInstall = $this->requiredCall('factory:enterprise-install', $enterpriseInstallArgs);
            $steps['06_enterprise_install'] = $this->writeStep($stepsDir, '06_enterprise_install', $enterpriseInstall);

            if (! $dryRun) {
                $dump = $this->shell('composer dump-autoload --no-interaction');
                $steps['07_composer_dump_autoload'] = $this->writeStep($stepsDir, '07_composer_dump_autoload', $dump);
                if ($dump['status'] !== 'passed') {
                    throw new RuntimeException('Falha ao atualizar o autoload após a instalação.');
                }

                $clear = $this->requiredCall('optimize:clear');
                $steps['08_optimize_clear'] = $this->writeStep($stepsDir, '08_optimize_clear', $clear);

                if ($migrate) {
                    $migration = $this->requiredCall('migrate', ['--force' => true]);
                    $steps['09_migrate'] = $this->writeStep($stepsDir, '09_migrate', $migration);
                }
            }
        } catch (Throwable $exception) {
            $status = 'failed';
            $blueprint ??= null;
            $steps['99_error'] = $this->writeStep($stepsDir, '99_error', [
                'class' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
        }

        $report = [
            'run_id' => $runId,
            'request' => $request,
            'status' => $status,
            'mode' => $dryRun ? 'dry_run' : 'install',
            'force' => $force,
            'migrate' => $migrate,
            'blueprint' => $blueprint,
            'steps' => $steps,
            'replayed' => false,
            'final_note' => $dryRun
                ? 'Dry-run concluído. Instalação real exige --install --confirm-production.'
                : 'Instalação executada. Verifique painel, migrations e logs.',
            'created_at' => now()->toISOString(),
        ];

        $path = $base . '/FINAL_MASTER_REPORT.json';
        File::put($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $report['path'] = $path;

        if ($idempotencyKey && $status === 'finished') {
            File::ensureDirectoryExists(dirname($idempotencyPath));
            File::put($idempotencyPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        return $report;
        } finally {
            $lock->release();
        }
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

    protected function requiredCall(string $command, array $args = []): array
    {
        $result = $this->call($command, array_filter($args, fn ($value) => $value !== false));

        if ($result['status'] !== 'passed') {
            throw new RuntimeException("Etapa obrigatória falhou: {$command}");
        }

        return $result;
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

    protected function detectLatestBlueprint(int $notBefore): ?string
    {
        $dir = storage_path('app/factory/finalization/productions');

        if (! File::isDirectory($dir)) {
            return null;
        }

        $file = collect(File::allFiles($dir))
            ->filter(fn ($item) => $item->getFilename() === 'finalization_report.json')
            ->filter(fn ($item) => $item->getMTime() >= $notBefore)
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
