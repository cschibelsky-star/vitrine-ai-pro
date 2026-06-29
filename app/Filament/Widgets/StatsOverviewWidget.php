<?php

namespace App\Filament\Widgets;

use App\Models\Company;
use App\Models\Lead;
use App\Models\License;
use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalClientes = Company::count();
        $licencasAtivas = License::where('status', 'Ativa')->count();
        $leadsAbertos = Lead::whereIn('status', ['Novo', 'Contato'])->count();
        $receitaMes = Payment::where('status', 'Pago')
            ->whereMonth('vencimento', now()->month)
            ->whereYear('vencimento', now()->year)
            ->sum('valor');

        return [
            Stat::make('Clientes', $totalClientes)->description('Total de empresas cadastradas')->color('primary')->icon('heroicon-o-building-office'),
            Stat::make('Licenças Ativas', $licencasAtivas)->description('Licenças com status Ativa')->color('success')->icon('heroicon-o-key'),
            Stat::make('Leads Abertos', $leadsAbertos)->description('Novo + Contato')->color('warning')->icon('heroicon-o-user-plus'),
            Stat::make('Receita do Mês', 'R$ ' . number_format((float) $receitaMes, 2, ',', '.'))->description('Pagamentos com status Pago no mês')->color('success')->icon('heroicon-o-currency-dollar'),
        ];
    }
}
