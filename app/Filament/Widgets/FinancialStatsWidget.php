<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Schema;

class FinancialStatsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function money(float|int $value): string
    {
        return 'R$ ' . number_format((float) $value, 2, ',', '.');
    }

    protected function amountColumn(): ?string
    {
        if (! Schema::hasTable('payments')) {
            return null;
        }

        foreach (['amount', 'valor', 'value'] as $column) {
            if (Schema::hasColumn('payments', $column)) {
                return $column;
            }
        }

        return null;
    }

    protected function getStats(): array
    {
        if (! class_exists(Payment::class) || ! Schema::hasTable('payments')) {
            return [
                Stat::make('Financeiro', 'R$ 0,00')
                    ->description('Módulo financeiro ainda não disponível')
                    ->color('gray'),
            ];
        }

        $amountColumn = $this->amountColumn();

        if (! $amountColumn) {
            return [
                Stat::make('Cobranças cadastradas', Payment::query()->count())
                    ->description('Tabela financeira encontrada, mas sem coluna de valor mapeada')
                    ->color('warning'),
            ];
        }

        $query = Payment::query();

        $paid = (clone $query)->whereIn('status', ['pago', 'Pago', 'paid'])->sum($amountColumn);
        $open = (clone $query)->whereIn('status', ['aberta', 'Aberta', 'pendente', 'Pendente', 'open'])->sum($amountColumn);
        $overdue = (clone $query)->whereIn('status', ['vencida', 'Vencida', 'overdue'])->sum($amountColumn);
        $total = (clone $query)->sum($amountColumn);

        return [
            Stat::make('Cobranças pagas', $this->money($paid))
                ->description('Receita confirmada no financeiro')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Cobranças abertas', $this->money($open))
                ->description('Valores aguardando pagamento')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Cobranças vencidas', $this->money($overdue))
                ->description('Atenção financeira necessária')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),

            Stat::make('Total financeiro lançado', $this->money($total))
                ->description('Soma geral das cobranças cadastradas')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),
        ];
    }
}
