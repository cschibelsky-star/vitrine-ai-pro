<?php

namespace App\Providers\Filament;

use App\Filament\Pages\AiCenterEnterprise;
use App\Filament\Pages\ClientPortalEnterprise;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\FactoryStudioEnterprise;
use App\Filament\Pages\GeneratedProjects;
use App\Filament\Pages\MarketplaceEnterprise;
use App\Filament\Resources\CompanyModuleResource;
use App\Filament\Resources\CompanyResource;
use App\Filament\Resources\ContractResource;
use App\Filament\Resources\LeadResource;
use App\Filament\Resources\LicenseResource;
use App\Filament\Resources\ModuleResource;
use App\Filament\Resources\PaymentResource;
use App\Filament\Resources\PlanModuleResource;
use App\Filament\Resources\PlanResource;
use App\Filament\Resources\ProductResource;
use App\Filament\Resources\SettingResource;
use App\Filament\Resources\SubscriptionResource;
use App\Filament\Resources\SupportTicketResource;
use App\Filament\Resources\UserResource;
use App\Filament\Widgets\StatsOverviewWidget;
use App\Filament\Widgets\UltimasLicencasWidget;
use App\Filament\Widgets\UltimosClientesWidget;
use App\Filament\Widgets\UltimosLeadsWidget;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('Vitrine IA Pro Enterprise')
            ->renderHook(
                'panels::head.end',
                fn (): string => '<link rel="stylesheet" href="' . asset('css/vitrine-enterprise-ui.css') . '?v=10.0.1">'
            )
            ->navigationGroups([
                NavigationGroup::make('01 · Centro Operacional'),
                NavigationGroup::make('02 · Operação'),
                NavigationGroup::make('03 · Comercial'),
                NavigationGroup::make('04 · Produtos e Licenças'),
                NavigationGroup::make('05 · Financeiro'),
                NavigationGroup::make('06 · Factory Studio'),
                NavigationGroup::make('07 · Projetos'),
                NavigationGroup::make('08 · Marketplace'),
                NavigationGroup::make('09 · Portal do Cliente'),
                NavigationGroup::make('10 · IA Center'),
                NavigationGroup::make('11 · Configurações'),
            ])
            ->colors([
                'primary' => Color::Sky,
            ])

            /*
            |--------------------------------------------------------------------------
            | Navegação Enterprise limpa
            |--------------------------------------------------------------------------
            | Não usamos discoverPages/discoverResources aqui.
            | O discover automático estava carregando páginas antigas, duplicadas e
            | módulos gerados pela Factory no menu principal.
            */

            ->pages([
                Dashboard::class,
                FactoryStudioEnterprise::class,
                GeneratedProjects::class,
                MarketplaceEnterprise::class,
                ClientPortalEnterprise::class,
                AiCenterEnterprise::class,
            ])

            ->resources([
                CompanyResource::class,
                ProductResource::class,
                PlanResource::class,
                LicenseResource::class,
                LeadResource::class,
                ContractResource::class,
                PaymentResource::class,
                SubscriptionResource::class,
                ModuleResource::class,
                PlanModuleResource::class,
                CompanyModuleResource::class,
                SupportTicketResource::class,
                UserResource::class,
                SettingResource::class,
            ])

            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                StatsOverviewWidget::class,
                UltimosClientesWidget::class,
                UltimasLicencasWidget::class,
                UltimosLeadsWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
