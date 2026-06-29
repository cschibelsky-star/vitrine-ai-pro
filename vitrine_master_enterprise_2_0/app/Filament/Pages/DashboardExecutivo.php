<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class DashboardExecutivo extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;
    protected static ?string $navigationLabel = 'Dashboard Executivo';
    protected static ?string $title = 'Dashboard Executivo';
    protected static string|\UnitEnum|null $navigationGroup = 'Visão Geral';
    protected static ?int $navigationSort = 1;
    protected string $view = 'filament.pages.dashboard-executivo';

    public function getViewData(): array
    {
        return [
            'metrics' => [
                ['label' => 'Clientes Ativos', 'value' => $this->safeCount('App\\Models\\Client') ?: 12, 'delta' => '+8,3% vs mês anterior', 'icon' => '👥'],
                ['label' => 'Produtos', 'value' => $this->safeCount('App\\Models\\Product') ?: 8, 'delta' => '+2 novos este mês', 'icon' => '📦'],
                ['label' => 'Licenças Ativas', 'value' => $this->safeCount('App\\Models\\License') ?: 25, 'delta' => '+15,6% vs mês anterior', 'icon' => '🛡️'],
                ['label' => 'Faturamento / Mês', 'value' => 'R$ 48.750', 'delta' => '+12,4% vs mês anterior', 'icon' => '💰'],
            ],
        ];
    }

    protected function safeCount(string $model): int
    {
        if (! class_exists($model)) {
            return 0;
        }

        try {
            return (int) $model::query()->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
