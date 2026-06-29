<?php

namespace App\Filament\Pages;

use App\Models\Company;
use App\Models\Contract;
use App\Models\Lead;
use App\Models\License;
use App\Models\Payment;
use App\Models\Product;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Dashboard Executivo';
    protected static ?string $title = 'Dashboard Executivo';
    protected static ?string $slug = 'dashboard';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.pages.dashboard-enterprise-real';

    public function getWidgets(): array
    {
        return [];
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    protected function getFooterWidgets(): array
    {
        return [];
    }

    public function getViewData(): array
    {
        $companies = class_exists(Company::class) ? Company::query()->count() : 0;
        $products = class_exists(Product::class) ? Product::query()->count() : 0;
        $licenses = class_exists(License::class) ? License::query()->count() : 0;
        $leads = class_exists(Lead::class) ? Lead::query()->count() : 0;
        $contracts = class_exists(Contract::class) ? Contract::query()->count() : 0;
        $payments = class_exists(Payment::class) ? Payment::query()->count() : 0;

        $paidRevenue = class_exists(Payment::class)
            ? (float) Payment::query()->whereIn('status', ['paid', 'pago', 'confirmed', 'confirmado'])->sum('amount')
            : 0;

        $totalRevenue = class_exists(Payment::class)
            ? (float) Payment::query()->sum('amount')
            : 0;

        $productsList = class_exists(Product::class)
            ? Product::query()->limit(5)->get(['name'])->pluck('name')->filter()->values()->all()
            : [];

        if (empty($productsList)) {
            $productsList = ['TV Digital Enterprise', 'Guia Digital da Cidade', 'Consultor AI GOV360', 'Portal News AI', 'SISMED'];
        }

        return [
            'metrics' => [
                ['label' => 'Clientes Ativos', 'value' => $companies, 'caption' => 'empresas, cidades e operações', 'icon' => '👥', 'tone' => 'blue'],
                ['label' => 'Produtos', 'value' => $products, 'caption' => 'catálogo do ecossistema', 'icon' => '📦', 'tone' => 'purple'],
                ['label' => 'Licenças Ativas', 'value' => $licenses, 'caption' => 'licenciamento SaaS', 'icon' => '🛡️', 'tone' => 'green'],
                ['label' => 'Faturamento', 'value' => 'R$ ' . number_format($paidRevenue ?: $totalRevenue, 2, ',', '.'), 'caption' => 'receita registrada', 'icon' => '💰', 'tone' => 'orange'],
            ],
            'quickActions' => [
                ['label' => 'Novo Cliente', 'caption' => 'Cadastrar empresa/cliente', 'url' => '/admin/companies/create', 'icon' => '+'],
                ['label' => 'Nova Licença', 'caption' => 'Ativar nova licença', 'url' => '/admin/licenses/create', 'icon' => '✓'],
                ['label' => 'Novo Lead', 'caption' => 'Cadastrar oportunidade', 'url' => '/admin/leads/create', 'icon' => '↗'],
                ['label' => 'Factory Studio', 'caption' => 'Criar projeto ou blueprint', 'url' => '/admin/factory-studio', 'icon' => '⚙'],
            ],
            'activity' => [
                ['title' => 'Leads recebidos', 'value' => $leads, 'time' => 'Funil comercial'],
                ['title' => 'Contratos / propostas', 'value' => $contracts, 'time' => 'Operação comercial'],
                ['title' => 'Pagamentos registrados', 'value' => $payments, 'time' => 'Financeiro'],
                ['title' => 'Projetos Factory', 'value' => 'online', 'time' => 'Ambiente ativo'],
            ],
            'products' => $productsList,
        ];
    }
}
