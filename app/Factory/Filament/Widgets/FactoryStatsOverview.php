<?php

declare(strict_types=1);

namespace App\Factory\Filament\Widgets;

use App\Factory\Services\FactoryDashboardService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Gate;

class FactoryStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $stats = app(FactoryDashboardService::class)->stats();

        return [
            Stat::make('Projetos', (string) ($stats['projects'] ?? 0))
                ->description(($stats['active_projects'] ?? 0) . ' ativos')
                ->descriptionIcon('heroicon-m-folder')
                ->color('primary'),
            Stat::make('Capabilities', (string) ($stats['capabilities'] ?? 0))
                ->description(($stats['active_capabilities'] ?? 0) . ' ativas')
                ->descriptionIcon('heroicon-m-bolt')
                ->color('success'),
            Stat::make('Blueprints', (string) ($stats['blueprints'] ?? 0))
                ->description(($stats['active_blueprints'] ?? 0) . ' ativos')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info'),
            Stat::make('Execuções', (string) ($stats['executions'] ?? 0))
                ->description(($stats['running_executions'] ?? 0) . ' em execução · ' . ($stats['failed_executions'] ?? 0) . ' falhas')
                ->descriptionIcon('heroicon-m-play-circle')
                ->color(($stats['failed_executions'] ?? 0) > 0 ? 'danger' : 'success'),
        ];
    }

    public static function canView(): bool
    {
        return auth()->check() && Gate::allows('factory.access');
    }
}
