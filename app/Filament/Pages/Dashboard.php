<?php

namespace App\Filament\Pages;

use App\Models\AiAgent;
use App\Models\Company;
use App\Models\CompanyModule;
use App\Models\License;
use App\Models\Module;
use App\Models\Plan;
use App\Models\Product;
use Filament\Pages\Page;

class Dashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-command-line';
    protected static ?string $navigationGroup = '01 · Centro Operacional';
    protected static ?string $navigationLabel = 'Command Center';
    protected static ?string $title = 'Vitrine IA Pro · Centro Operacional';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.pages.dashboard';

    public function metrics(): array
    {
        $activeLicenseStatuses = ['Ativa', 'Homologação', 'Trial'];

        return [
            'companies' => Company::count(),
            'active_companies' => Company::where('status', 'Ativo')->count(),
            'products' => Product::where('status', 'Ativo')->count(),
            'licenses' => License::count(),
            'active_licenses' => License::whereIn('status', $activeLicenseStatuses)->count(),
            'licensed_value' => (float) License::whereIn('status', $activeLicenseStatuses)->sum('valor'),
            'agents' => AiAgent::count(),
            'modules' => Module::count(),
            'company_modules' => CompanyModule::count(),
            'plans' => Plan::count(),
        ];
    }

    public function expiringLicenses(): int
    {
        return License::whereDate('vencimento', '>=', now()->toDateString())
            ->whereDate('vencimento', '<=', now()->addDays(30)->toDateString())
            ->count();
    }

    public function implementationCompanies(): int
    {
        return Company::whereIn('status_implantacao', ['Em implantação', 'Homologação'])->count();
    }

    public function getProducts(): array
    {
        return Product::query()
            ->withCount(['plans', 'licenses', 'modules'])
            ->orderBy('nome')
            ->get()
            ->map(fn (Product $product) => [
                'name' => $product->nome,
                'status' => $product->status,
                'plans' => $product->plans_count,
                'licenses' => $product->licenses_count,
                'modules' => $product->modules_count,
            ])
            ->all();
    }

    public function recentLicenses(): array
    {
        return License::query()
            ->with(['company:id,nome', 'product:id,nome', 'plan:id,nome'])
            ->latest('updated_at')
            ->limit(6)
            ->get()
            ->map(fn (License $license) => [
                'company' => $license->company?->nome ?? '—',
                'product' => $license->product?->nome ?? '—',
                'plan' => $license->plan?->nome ?? $license->plano ?? '—',
                'status' => $license->status,
                'value' => (float) $license->valor,
                'expires' => $license->vencimento?->format('d/m/Y') ?? '—',
            ])
            ->all();
    }
}
