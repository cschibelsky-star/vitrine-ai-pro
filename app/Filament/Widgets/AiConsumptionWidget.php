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
        $today = AiConsumption::query()->whereDate('consumption_date', now()->toDateString());
        $month = AiConsumption::query()->whereBetween('consumption_date', [
            now()->startOfMonth()->toDateString(),
            now()->endOfMonth()->toDateString(),
        ]);

        $todayCost = (float) (clone $today)->sum('cost_brl');
        $monthCost = (float) (clone $month)->sum('cost_brl');
        $monthEstimated = (float) (clone $month)->sum('estimated_cost');
        $monthQuantity = (float) (clone $month)->sum('quantity');
        $topGateway = (clone $month)
            ->whereNotNull('gateway')
            ->selectRaw('gateway, SUM(COALESCE(cost_brl, estimated_cost, 0)) as total_cost')
            ->groupBy('gateway')
            ->orderByDesc('total_cost')
            ->first();

        return [
            Stat::make('Custo IA hoje', 'R$ ' . number_format($todayCost, 2, ',', '.'))
                ->description('Custo real consolidado')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('info'),
            Stat::make('Custo IA mês', 'R$ ' . number_format($monthCost, 2, ',', '.'))
                ->description('Real | estimado R$ ' . number_format($monthEstimated, 2, ',', '.'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),
            Stat::make('Consumo no mês', number_format($monthQuantity, 0, ',', '.'))
                ->description('Unidades registradas')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('success'),
            Stat::make('Gateway de maior custo', $topGateway?->gateway ?? 'Sem dados')
                ->description($topGateway ? 'R$ ' . number_format((float) $topGateway->total_cost, 2, ',', '.') : 'Aguardando telemetria')
                ->descriptionIcon('heroicon-m-server-stack')
                ->color('warning'),
        ];
    }
}
