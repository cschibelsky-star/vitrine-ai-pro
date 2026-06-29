<?php

declare(strict_types=1);

namespace App\Factory\Finalization\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class FinalizationProductionService
{
    public function __construct(
        protected AiArchitectFinalService $architect,
    ) {
    }

    public function produce(string $request): array
    {
        $architecture = $this->architect->architect($request);
        $blueprint = $architecture['blueprint'];

        $base = storage_path('app/factory/finalization/productions/' . date('Ymd_His') . '_' . $blueprint['slug']);
        $stepsDir = $base . '/steps';

        File::ensureDirectoryExists($stepsDir);

        $status = 'finished';
        $steps = [];

        $steps['01_architecture'] = $this->writeStep($stepsDir, '01_architecture', $architecture);

        if (($architecture['domain'] ?? '') === 'gov360_known') {
            $result = $this->call('factory:produce', ['product' => 'gov360']);
            $steps['02_known_product_production'] = $this->writeStep($stepsDir, '02_known_product_production', $result);
            if ($result['status'] !== 'passed') {
                $status = 'failed';
            }
        } else {
            $build = $this->call('factory:make-system', ['slug' => $blueprint['slug']]);
            $steps['02_make_system'] = $this->writeStep($stepsDir, '02_make_system', $build);

            if ($build['status'] !== 'passed') {
                $status = 'failed';
            }

            $dashboards = [];

            foreach (($blueprint['modules'] ?? []) as $module) {
                $slug = $module['slug'];
                $dashboards[$slug] = [
                    'dashboard' => $this->call('factory:dashboard-module', ['slug' => $slug]),
                    'widgets' => $this->call('factory:widgets-module', ['slug' => $slug]),
                    'qa' => $this->call('factory:smart-qa', ['slug' => $slug]),
                ];
            }

            $dashboards['executive'] = $this->call('factory:executive-dashboard', ['slug' => $blueprint['slug']]);
            $steps['03_dashboard_widget_qa'] = $this->writeStep($stepsDir, '03_dashboard_widget_qa', $dashboards);
        }

        $systemQa = $this->call('factory:smart-qa2');
        $steps['04_system_qa'] = $this->writeStep($stepsDir, '04_system_qa', $systemQa);

        if ($systemQa['status'] !== 'passed') {
            $status = 'failed';
        }

        $report = [
            'request' => $request,
            'status' => $status,
            'domain' => $architecture['domain'],
            'blueprint_slug' => $blueprint['slug'],
            'blueprint_path' => $architecture['blueprint_path'],
            'modules' => array_map(fn ($module) => $module['slug'], $blueprint['modules'] ?? []),
            'production_path' => $base,
            'steps' => $steps,
            'next_command' => 'php artisan factory:install-final ' . $blueprint['slug'] . ' --dry-run',
            'produced_at' => now()->toISOString(),
        ];

        $path = $base . '/finalization_report.json';
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

    protected function writeStep(string $dir, string $name, array $payload): string
    {
        $path = $dir . '/' . $name . '.json';
        File::put($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $path;
    }
}
