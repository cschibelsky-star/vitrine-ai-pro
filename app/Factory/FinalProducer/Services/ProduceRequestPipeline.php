<?php

declare(strict_types=1);

namespace App\Factory\FinalProducer\Services;

use App\Factory\Decision\Services\DecisionEngine;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class ProduceRequestPipeline
{
    public function __construct(
        protected ProductRequestResolver $resolver,
        protected DecisionEngine $decisionEngine,
    ) {
    }

    public function run(string $request, bool $approved = false): array
    {
        $resolved = $this->resolver->resolve($request);
        $decision = $this->decisionEngine->decide($request);
        $product = $resolved['resolved_product'];
        $domain = (string) ($resolved['domain'] ?? 'generico');
        $key = $product ?: $domain;

        $base = storage_path('app/factory/final-producer/requests/' . date('Ymd_His') . '_' . $key);
        File::ensureDirectoryExists($base);

        $resolverPath = $base . '/01_resolver.json';
        $decisionPath = $base . '/02_decision.json';
        File::put($resolverPath, json_encode($resolved, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        File::put($decisionPath, json_encode($decision, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        if (! $approved) {
            $report = [
                'request' => $request,
                'domain' => $domain,
                'resolved_product' => $product,
                'status' => 'awaiting_approval',
                'resolver_path' => $resolverPath,
                'decision_path' => $decisionPath,
                'next_command' => 'php artisan factory:produce-request --approved <request>',
                'created_at' => now()->toISOString(),
            ];

            return $this->writeReport($base, $report);
        }

        if (! $product) {
            $report = [
                'request' => $request,
                'domain' => $domain,
                'resolved_product' => null,
                'status' => 'awaiting_materialization',
                'resolver_path' => $resolverPath,
                'decision_path' => $decisionPath,
                'next_command' => 'materialize approved blueprint for domain ' . $domain,
                'created_at' => now()->toISOString(),
            ];

            return $this->writeReport($base, $report);
        }

        $exitCode = Artisan::call('factory:produce', ['product' => $product]);
        $production = [
            'command' => 'factory:produce ' . $product,
            'exit_code' => $exitCode,
            'status' => $exitCode === 0 ? 'passed' : 'failed',
            'output' => Artisan::output(),
        ];

        $productionPath = $base . '/03_production.json';
        File::put($productionPath, json_encode($production, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $report = [
            'request' => $request,
            'domain' => $domain,
            'resolved_product' => $product,
            'status' => $production['status'] === 'passed' ? 'finished' : 'failed',
            'resolver_path' => $resolverPath,
            'decision_path' => $decisionPath,
            'production_step_path' => $productionPath,
            'production_report_path' => storage_path('app/factory/production/' . $product . '/production_report.json'),
            'next_command' => 'php artisan factory:install-system ' . $product . ' --dry-run',
            'created_at' => now()->toISOString(),
        ];

        return $this->writeReport($base, $report);
    }

    protected function writeReport(string $base, array $report): array
    {
        $reportPath = $base . '/final_request_report.json';
        File::put($reportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $report['path'] = $reportPath;

        return $report;
    }
}
