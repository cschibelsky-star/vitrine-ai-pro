<?php

namespace App\Console\Commands;

use App\CommercialFactory\Services\CommercialFactoryIntakeService;
use App\Factory\FinalProducer\Services\ProduceRequestPipeline;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Throwable;

class FactoryClosureTestCommand extends Command
{
    protected $signature = 'test';
    protected $description = 'Executa validações read-only de fechamento da Factory.';

    public function handle(
        ProduceRequestPipeline $pipeline,
        CommercialFactoryIntakeService $commercialIntake,
    ): int {
        try {
            $producerRoot = storage_path('app/factory/final-producer/requests');
            $commercialRoot = storage_path('app/factory/commercial-intake');
            $producerBefore = $this->fileCount($producerRoot);
            $commercialBefore = $this->fileCount($commercialRoot);

            $request = 'Crie um sistema de captação de recursos, oportunidades, matching, análise, documentos e plano de ação';
            $report = $pipeline->run($request, false);

            $this->assertSame('captacao_recursos', $report['domain'] ?? null, 'domain');
            $this->assertSame('awaiting_approval', $report['status'] ?? null, 'producer_status');
            $this->assertSame(false, $report['persisted'] ?? null, 'producer_persisted');
            $this->assertSame(null, $report['path'] ?? null, 'producer_path');

            $blueprint = $report['resolved']['blueprint'] ?? [];
            $this->assertSame('captacao_recursos_oportunidades', $blueprint['slug'] ?? null, 'blueprint_slug');
            $this->assertSame(6, count($blueprint['modules'] ?? []), 'module_count');
            $this->assertSame($producerBefore, $this->fileCount($producerRoot), 'producer_file_count');

            $intakeData = [
                'product' => 'TV Digital Enterprise',
                'client' => 'Factory Closure Validation',
                'plan' => 'enterprise',
                'domain' => 'factory-closure.local',
            ];
            $intake = $commercialIntake->intake($intakeData, true, false, null);

            $this->assertSame('awaiting_approval', $intake['status'] ?? null, 'intake_status');
            $this->assertSame(false, $intake['persisted'] ?? null, 'intake_persisted');
            $this->assertSame(null, $intake['path'] ?? null, 'intake_path');
            $this->assertTrue(is_string($intake['approval_token'] ?? null) && $intake['approval_token'] !== '', 'approval_token');
            $this->assertSame($commercialBefore, $this->fileCount($commercialRoot), 'intake_file_count');

            $approvedDryRun = $commercialIntake->intake(
                $intakeData,
                true,
                true,
                $intake['approval_token'],
            );
            $this->assertSame('approved_dry_run', $approvedDryRun['status'] ?? null, 'approved_dry_run_status');
            $this->assertSame(false, $approvedDryRun['persisted'] ?? null, 'approved_dry_run_persisted');
            $this->assertSame($commercialBefore, $this->fileCount($commercialRoot), 'approved_dry_run_file_count');

            $this->info('FACTORY_CLOSURE_TEST=PASS');
            $this->line('domain=captacao_recursos');
            $this->line('slug=captacao_recursos_oportunidades');
            $this->line('modules=6');
            $this->line('pre_approval_persisted=false');
            $this->line('commercial_pre_approval_persisted=false');
            $this->line('approval_token=validated');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('FACTORY_CLOSURE_TEST=FAIL');
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    private function fileCount(string $path): int
    {
        return File::isDirectory($path) ? count(File::allFiles($path)) : 0;
    }

    private function assertSame(mixed $expected, mixed $actual, string $label): void
    {
        if ($expected !== $actual) {
            throw new \RuntimeException($label . '_mismatch');
        }
    }

    private function assertTrue(bool $condition, string $label): void
    {
        if (! $condition) {
            throw new \RuntimeException($label . '_failed');
        }
    }
}
