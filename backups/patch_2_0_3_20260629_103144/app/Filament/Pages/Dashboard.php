<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\DB;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Dashboard Executivo';
    protected static ?string $title = 'Dashboard Executivo';
    protected static ?string $slug = 'dashboard-executivo';
    protected static string $view = 'filament.pages.dashboard-enterprise-ui';
    protected static ?int $navigationSort = -10;

    public function getColumns(): int | array
    {
        return 1;
    }

    public function getWidgets(): array
    {
        return [];
    }

    public function metric(string $table, string $fallback = '0'): string
    {
        try {
            if (! DB::getSchemaBuilder()->hasTable($table)) {
                return $fallback;
            }

            return number_format((int) DB::table($table)->count(), 0, ',', '.');
        } catch (\Throwable $e) {
            return $fallback;
        }
    }

    public function money(string $table, string $column = 'valor', string $fallback = 'R$ 0,00'): string
    {
        try {
            if (! DB::getSchemaBuilder()->hasTable($table) || ! DB::getSchemaBuilder()->hasColumn($table, $column)) {
                return $fallback;
            }

            $value = (float) DB::table($table)->sum($column);

            return 'R$ ' . number_format($value, 2, ',', '.');
        } catch (\Throwable $e) {
            return $fallback;
        }
    }

    public function getExecutiveMetrics(): array
    {
        return [
            [
                'label' => 'Clientes Ativos',
                'value' => $this->metric('companies', $this->metric('clientes')),
                'hint' => '+ visão comercial',
                'icon' => '👥',
                'tone' => 'blue',
            ],
            [
                'label' => 'Produtos',
                'value' => $this->metric('products', $this->metric('produtos')),
                'hint' => 'catálogo SaaS',
                'icon' => '📦',
                'tone' => 'purple',
            ],
            [
                'label' => 'Licenças Ativas',
                'value' => $this->metric('licenses', $this->metric('licencas')),
                'hint' => 'recorrência',
                'icon' => '🛡️',
                'tone' => 'green',
            ],
            [
                'label' => 'Faturamento',
                'value' => $this->money('payments', 'amount', $this->money('financeiros', 'valor', 'R$ 0,00')),
                'hint' => 'total lançado',
                'icon' => '💰',
                'tone' => 'orange',
            ],
        ];
    }

    public function getActivities(): array
    {
        return [
            ['title' => 'Nova licença ativada', 'detail' => 'TV Digital Enterprise - Licença operacional', 'time' => 'agora', 'icon' => '🛡️'],
            ['title' => 'Lead cadastrado', 'detail' => 'Interesse em TV Digital Enterprise', 'time' => 'hoje', 'icon' => '👤'],
            ['title' => 'Pagamento recebido', 'detail' => 'Receita confirmada no financeiro', 'time' => 'hoje', 'icon' => '💰'],
            ['title' => 'Projeto Factory atualizado', 'detail' => 'Blueprint e execução em revisão', 'time' => 'recente', 'icon' => '🏭'],
        ];
    }

    public function getPopularProducts(): array
    {
        return [
            ['name' => 'TV Digital Enterprise', 'value' => 40],
            ['name' => 'Guia Digital da Cidade', 'value' => 24],
            ['name' => 'Portal News AI', 'value' => 16],
            ['name' => 'Município Digital IA', 'value' => 12],
            ['name' => 'SISMED', 'value' => 8],
        ];
    }
}
