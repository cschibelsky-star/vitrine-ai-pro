<?php

declare(strict_types=1);

namespace App\Factory\Dashboard\Services;

use Illuminate\Support\Facades\File;

class ExecutiveDashboardGenerator
{
    public function generate(string $systemSlug): array
    {
        $blueprintPath = storage_path('app/factory/blueprints/' . $systemSlug . '.json');

        if (! File::exists($blueprintPath)) {
            throw new \RuntimeException("Blueprint não encontrado: {$blueprintPath}");
        }

        $blueprint = json_decode((string) File::get($blueprintPath), true);

        $modules = $blueprint['modules'] ?? [];

        $cards = [];
        $alerts = [];
        $charts = [];

        foreach ($modules as $module) {
            $cards[] = [
                'key' => 'total_' . $module['slug'],
                'label' => 'Total de ' . $module['label'],
                'module' => $module['slug'],
                'type' => 'count',
            ];

            foreach (($module['dashboard_metrics'] ?? []) as $metric) {
                $cards[] = [
                    'key' => $module['slug'] . '_' . $metric,
                    'label' => ucfirst(str_replace('_', ' ', $metric)) . ' - ' . $module['label'],
                    'module' => $module['slug'],
                    'type' => 'metric',
                ];
            }

            $fieldNames = implode(' ', array_map(fn ($field) => $field['name'] ?? '', $module['fields'] ?? []));

            if (str_contains($fieldNames, 'status')) {
                $charts[] = [
                    'key' => $module['slug'] . '_status',
                    'label' => $module['label'] . ' por status',
                    'module' => $module['slug'],
                    'type' => 'donut',
                ];
            }

            if (str_contains($fieldNames, 'data_fim') || str_contains($fieldNames, 'validade')) {
                $alerts[] = [
                    'key' => $module['slug'] . '_vencimentos',
                    'label' => $module['label'] . ' com vencimento próximo',
                    'module' => $module['slug'],
                    'type' => 'expiration_alert',
                ];
            }

            if (str_contains($fieldNames, 'valor')) {
                $cards[] = [
                    'key' => $module['slug'] . '_valor_total',
                    'label' => 'Valor total - ' . $module['label'],
                    'module' => $module['slug'],
                    'type' => 'currency_sum',
                ];
            }
        }

        $dashboard = [
            'system' => $systemSlug,
            'title' => 'Dashboard Executivo — ' . ($blueprint['name'] ?? $systemSlug),
            'cards' => $cards,
            'charts' => $charts,
            'alerts' => $alerts,
            'generated_at' => now()->toISOString(),
        ];

        $dir = storage_path('app/factory/dashboards/systems');
        File::ensureDirectoryExists($dir);

        $path = $dir . '/' . $systemSlug . '.json';
        File::put($path, json_encode($dashboard, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $dashboard['path'] = $path;

        return $dashboard;
    }
}
