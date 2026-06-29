<?php

namespace App\Filament\Widgets;

use App\Models\AiAlert;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AiAlertsWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected function getStats(): array
    {
        $critical = class_exists(AiAlert::class) ? AiAlert::where('status', 'aberto')->where('severity', 'critica')->count() : 0;
        $high = class_exists(AiAlert::class) ? AiAlert::where('status', 'aberto')->where('severity', 'alta')->count() : 0;
        $medium = class_exists(AiAlert::class) ? AiAlert::where('status', 'aberto')->where('severity', 'media')->count() : 0;
        $resolved = class_exists(AiAlert::class) ? AiAlert::where('status', 'resolvido')->count() : 0;

        return [
            Stat::make('Críticos', $critical)->description('Intervenção imediata')->color($critical > 0 ? 'danger' : 'success'),
            Stat::make('Alta prioridade', $high)->description('Acompanhar operação')->color($high > 0 ? 'warning' : 'success'),
            Stat::make('Média prioridade', $medium)->description('Atenção operacional')->color('info'),
            Stat::make('Resolvidos', $resolved)->description('Histórico de tratamento')->color('gray'),
        ];
    }
}
