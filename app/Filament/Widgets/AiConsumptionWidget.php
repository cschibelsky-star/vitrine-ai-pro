<?php

namespace App\Filament\Widgets;

use App\Models\AiConsumption;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AiConsumptionWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $todayCost = class_exists(AiConsumption::class)
            ? (float) AiConsumption::whereDate('consumption_date', now()->toDateString())->sum('estimated_cost')
            : 0;

        $monthCost = class_exists(AiConsumption::class)
            ? (float) AiConsumption::whereBetween('consumption_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])->sum('estimated_cost')
            : 0;

        $monthQuantity = class_exists(AiConsumption::class)
            ? (float) AiConsumption::whereBetween('consumption_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])->sum('quantity')
            : 0;

        return [
            Stat::make('Custo IA hoje', 'R$ ' . number_format($todayCost, 2, ',', '.'))->description('Estimativa diária')->descriptionIcon('heroicon-m-currency-dollar')->color('info'),
            Stat::make('Custo IA mês', 'R$ ' . number_format($monthCost, 2, ',', '.'))->description('Estimativa mensal')->descriptionIcon('heroicon-m-banknotes')->color('primary'),
            Stat::make('Consumo no mês', number_format($monthQuantity, 0, ',', '.'))->description('Unidades registradas')->descriptionIcon('heroicon-m-chart-bar')->color('success'),
        ];
    }
}
