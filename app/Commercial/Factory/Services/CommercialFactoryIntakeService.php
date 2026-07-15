<?php

declare(strict_types=1);

namespace App\Commercial\Factory\Services;

use App\Factory\Enums\FactoryStage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CommercialFactoryIntakeService
{
    public function __construct(protected CommercialProductResolver $resolver) {}

    public function intake(array $data, bool $dryRun = true): array
    {
        $resolved = $this->resolver->resolve((string) $data['product']);
        $product = $resolved['config'];
        $planKey = (string) ($data['plan'] ?? 'start');
        $plan = $product['plans'][$planKey] ?? reset($product['plans']);
        $clientSlug = Str::slug((string) $data['client'], '_');
        $projectSlug = $resolved['key'].'_'.$clientSlug;
        $base = storage_path('app/factory/commercial-intake/'.date('Ymd_His').'_'.$projectSlug);
        File::ensureDirectoryExists($base);

        $prompt = trim(($product['factory_prompt'] ?? '')."\n\nCliente: ".($data['client'] ?? '')."\nPlano: ".($plan['label'] ?? $planKey)."\nDomínio: ".($data['domain'] ?? ''));

        File::put($base.'/commercial_intake.json', json_encode([
            'client' => $data['client'],
            'product' => $resolved['name'],
            'plan' => $planKey,
            'project_slug' => $projectSlug,
            'prompt' => $prompt,
            'dry_run' => $dryRun,
            'stage' => FactoryStage::IntakeReceived->value,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $arguments = ['request' => [$prompt]];
        if ($dryRun) {
            $arguments['--dry-run'] = true;
        }

        $exitCode = Artisan::call('factory:build-and-install', $arguments);
        $succeeded = $exitCode === 0;
        $stage = match (true) {
            ! $succeeded => FactoryStage::Failed,
            $dryRun => FactoryStage::SimulationCompleted,
            default => FactoryStage::BuildCompleted,
        };

        $report = [
            'status' => $succeeded ? 'success' : 'failed',
            'stage' => $stage->value,
            'project_slug' => $projectSlug,
            'commercial_status' => $stage->value,
            'dry_run' => $dryRun,
            'exit_code' => $exitCode,
            'path' => $base.'/commercial_factory_report.json',
            'created_at' => now()->toISOString(),
        ];

        File::put($report['path'], json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        File::put($base.'/factory_output.txt', Artisan::output());

        return $report;
    }
}
