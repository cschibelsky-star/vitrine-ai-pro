<?php

namespace App\Filament\Widgets;

use App\Models\AiAgent;
use App\Models\AiExecution;
use App\Models\AiQueue;
use App\Models\AiAlert;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AiStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $activeAgents = class_exists(AiAgent::class) ? AiAgent::where('status', 'online')->count() : 0;
        $executionsToday = class_exists(AiExecution::class) ? AiExecution::whereDate('created_at', now()->toDateString())->count() : 0;
        $pendingQueues = class_exists(AiQueue::class) ? AiQueue::whereIn('status', ['pendente', 'processando'])->count() : 0;
        $openAlerts = class_exists(AiAlert::class) ? AiAlert::where('status', 'aberto')->count() : 0;

        return [
            Stat::make('Agentes ativos', $activeAgents)->description('Agentes online no ACC')->descriptionIcon('heroicon-m-cpu-chip')->color('success'),
            Stat::make('Execuções hoje', $executionsToday)->description('Processamentos registrados hoje')->descriptionIcon('heroicon-m-bolt')->color('info'),
            Stat::make('Filas abertas', $pendingQueues)->description('Pendentes ou processando')->descriptionIcon('heroicon-m-arrow-path')->color('warning'),
            Stat::make('Alertas abertos', $openAlerts)->description('Ocorrências não resolvidas')->descriptionIcon('heroicon-m-exclamation-triangle')->color($openAlerts > 0 ? 'danger' : 'success'),
        ];
    }
}
