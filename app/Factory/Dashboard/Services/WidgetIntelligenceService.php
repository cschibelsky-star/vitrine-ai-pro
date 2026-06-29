<?php

declare(strict_types=1);

namespace App\Factory\Dashboard\Services;

use Illuminate\Support\Facades\File;

class WidgetIntelligenceService
{
    public function generate(string $slug): array
    {
        $modulePath = storage_path('app/factory/builds/' . $slug);
        $manifestPath = $modulePath . '/module.json';

        $manifest = File::exists($manifestPath)
            ? json_decode((string) File::get($manifestPath), true)
            : [];

        $widgets = [
            [
                'key' => 'latest_records',
                'label' => 'Últimos registros',
                'type' => 'table',
                'reason' => 'Todo módulo precisa de uma visão rápida dos últimos cadastros.',
            ],
            [
                'key' => 'monthly_growth',
                'label' => 'Crescimento mensal',
                'type' => 'line_chart',
                'reason' => 'Permite acompanhar evolução do módulo.',
            ],
        ];

        $text = strtolower($slug . ' ' . json_encode($manifest, JSON_UNESCAPED_UNICODE));

        if (str_contains($text, 'cidade')) {
            $widgets[] = [
                'key' => 'by_city',
                'label' => 'Distribuição por cidade',
                'type' => 'bar_chart',
                'reason' => 'Campo cidade detectado.',
            ];
        }

        if (str_contains($text, 'categoria') || str_contains($text, 'categoria_id')) {
            $widgets[] = [
                'key' => 'by_category',
                'label' => 'Distribuição por categoria',
                'type' => 'donut_chart',
                'reason' => 'Relacionamento com categoria detectado.',
            ];
        }

        if (str_contains($text, 'status')) {
            $widgets[] = [
                'key' => 'by_status',
                'label' => 'Registros por status',
                'type' => 'status_cards',
                'reason' => 'Campo status detectado.',
            ];
        }

        if (str_contains($text, 'valor')) {
            $widgets[] = [
                'key' => 'financial_total',
                'label' => 'Total financeiro',
                'type' => 'currency_kpi',
                'reason' => 'Campo de valor detectado.',
            ];
        }

        if (str_contains($text, 'documento')) {
            $widgets[] = [
                'key' => 'documents_pending',
                'label' => 'Documentos pendentes',
                'type' => 'alert_card',
                'reason' => 'Padrão de documentos detectado.',
            ];
        }

        if (str_contains($text, 'contrato')) {
            $widgets[] = [
                'key' => 'contracts_expiring',
                'label' => 'Contratos vencendo',
                'type' => 'alert_card',
                'reason' => 'Padrão de contratos detectado.',
            ];
        }

        $result = [
            'module' => $slug,
            'widgets' => $widgets,
            'generated_at' => now()->toISOString(),
        ];

        $dir = storage_path('app/factory/widgets/modules');
        File::ensureDirectoryExists($dir);

        $path = $dir . '/' . $slug . '.json';
        File::put($path, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $result['path'] = $path;

        return $result;
    }
}
