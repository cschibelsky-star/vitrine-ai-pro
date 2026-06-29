<?php

declare(strict_types=1);

namespace App\Factory\Dashboard\Services;

use Illuminate\Support\Facades\File;

class ModuleDashboardGenerator
{
    public function generate(string $slug): array
    {
        $modulePath = storage_path('app/factory/builds/' . $slug);
        $manifestPath = $modulePath . '/module.json';

        $manifest = File::exists($manifestPath)
            ? json_decode((string) File::get($manifestPath), true)
            : [];

        $dashboard = [
            'module' => $slug,
            'title' => $this->title($slug),
            'source_manifest' => $manifest,
            'cards' => [
                [
                    'key' => 'total',
                    'label' => 'Total de registros',
                    'type' => 'count',
                    'icon' => 'heroicon-o-chart-bar',
                ],
                [
                    'key' => 'ativos',
                    'label' => 'Registros ativos',
                    'type' => 'status_count',
                    'status' => 'active',
                    'icon' => 'heroicon-o-check-circle',
                ],
                [
                    'key' => 'inativos',
                    'label' => 'Registros inativos',
                    'type' => 'status_count',
                    'status' => 'inactive',
                    'icon' => 'heroicon-o-x-circle',
                ],
                [
                    'key' => 'novos_mes',
                    'label' => 'Novos este mês',
                    'type' => 'monthly_count',
                    'icon' => 'heroicon-o-calendar-days',
                ],
            ],
            'tables' => [
                [
                    'key' => 'ultimos',
                    'label' => 'Últimos registros',
                    'limit' => 5,
                ],
            ],
            'generated_at' => now()->toISOString(),
        ];

        $dir = storage_path('app/factory/dashboards/modules');
        File::ensureDirectoryExists($dir);

        $path = $dir . '/' . $slug . '.json';
        File::put($path, json_encode($dashboard, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $dashboard['path'] = $path;

        return $dashboard;
    }

    protected function title(string $slug): string
    {
        return str($slug)->replace('_', ' ')->headline()->toString();
    }
}
