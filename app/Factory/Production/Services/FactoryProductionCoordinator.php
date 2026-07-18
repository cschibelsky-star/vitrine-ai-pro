<?php

declare(strict_types=1);

namespace App\Factory\Production\Services;

use App\Factory\ProductionEnterprise\Services\EnterpriseProductionEngine;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

final class FactoryProductionCoordinator
{
    public function __construct(
        private FactoryProductionOrchestrator $classic,
        private EnterpriseProductionEngine $enterprise,
    ) {
    }

    public function produce(
        string $product,
        ?string $pipeline = null,
        ?string $idempotencyKey = null,
    ): array {
        $pipeline ??= (string) config('factory_operational.canonical_pipeline', 'classic');
        $allowed = (array) config('factory_operational.pipelines', ['classic', 'enterprise']);

        if (! in_array($pipeline, $allowed, true)) {
            throw new RuntimeException("Pipeline inválido: {$pipeline}");
        }

        if ($idempotencyKey !== null && trim($idempotencyKey) !== '') {
            $replayed = $this->replay($product, $pipeline, $idempotencyKey);

            if ($replayed !== null) {
                return $replayed;
            }
        }

        $runId = (string) Str::uuid();
        $runDir = storage_path("app/factory/runs/{$runId}");
        $lock = Cache::lock(
            'factory:produce:' . hash('sha256', $product),
            max(60, (int) config('factory_operational.lock_seconds', 3600)),
        );
        $lockAcquired = false;

        try {
            if (! $lock->get()) {
                throw new RuntimeException("Já existe uma produção em andamento para {$product}.");
            }

            $lockAcquired = true;

            $this->state($runDir, [
                'run_id' => $runId,
                'product' => $product,
                'pipeline' => $pipeline,
                'status' => 'running',
                'started_at' => now()->toISOString(),
            ]);

            $report = $pipeline === 'enterprise'
                ? $this->enterprise->produce($product)
                : $this->classic->produce($product);

            $report = array_merge($report, [
                'run_id' => $runId,
                'pipeline' => $pipeline,
                'replayed' => false,
            ]);

            $reportPath = $runDir . '/report.json';
            $report['coordinator_report_path'] = $reportPath;
            $this->atomicJson($reportPath, $report);

            $this->state($runDir, [
                'run_id' => $runId,
                'product' => $product,
                'pipeline' => $pipeline,
                'status' => ($report['status'] ?? 'failed') === 'finished' ? 'completed' : 'failed',
                'report_path' => $reportPath,
                'finished_at' => now()->toISOString(),
            ]);

            if (($report['status'] ?? 'failed') === 'finished' && $idempotencyKey !== null && trim($idempotencyKey) !== '') {
                $this->remember($product, $pipeline, $idempotencyKey, $reportPath);
            }

            return $report;
        } catch (Throwable $exception) {
            $this->state($runDir, [
                'run_id' => $runId,
                'product' => $product,
                'pipeline' => $pipeline,
                'status' => 'failed',
                'error_class' => $exception::class,
                'error_message' => $exception->getMessage(),
                'finished_at' => now()->toISOString(),
            ]);

            throw $exception;
        } finally {
            if ($lockAcquired) {
                $lock->release();
            }
        }
    }

    private function replay(string $product, string $pipeline, string $key): ?array
    {
        $index = $this->idempotencyPath($product, $pipeline, $key);

        if (! File::exists($index)) {
            return null;
        }

        $entry = json_decode((string) File::get($index), true);
        $reportPath = $entry['report_path'] ?? null;

        if (! is_string($reportPath) || ! File::exists($reportPath)) {
            return null;
        }

        $report = json_decode((string) File::get($reportPath), true);
        $report['replayed'] = true;

        return $report;
    }

    private function remember(string $product, string $pipeline, string $key, string $reportPath): void
    {
        $this->atomicJson($this->idempotencyPath($product, $pipeline, $key), [
            'product' => $product,
            'pipeline' => $pipeline,
            'key_hash' => hash('sha256', $key),
            'report_path' => $reportPath,
            'created_at' => now()->toISOString(),
        ]);
    }

    private function idempotencyPath(string $product, string $pipeline, string $key): string
    {
        return storage_path('app/factory/idempotency/' . hash('sha256', "{$pipeline}:{$product}:{$key}") . '.json');
    }

    private function state(string $runDir, array $state): void
    {
        $this->atomicJson($runDir . '/state.json', $state);
    }

    private function atomicJson(string $path, array $payload): void
    {
        File::ensureDirectoryExists(dirname($path));
        $temporary = $path . '.tmp.' . Str::uuid();
        File::put($temporary, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        File::move($temporary, $path);
    }
}
