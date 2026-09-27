<?php

namespace App\Providers\Filament;

use App\Filament\Pages\AiCenterEnterprise;
use App\Filament\Pages\ClientPortalEnterprise;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\FactoryStudioEnterprise;
use App\Filament\Pages\GeneratedProjects;
use App\Filament\Pages\MarketingDashboard;
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
            ->homeUrl(fn (): string => MarketingDashboard::getUrl())
            ->brandName('VITRINE IA PRO · MARKETING IA')
            ->colors([
                'primary' => Color::Violet,
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
                MarketingDashboard::class,
            ])

            ->resources([])
            ->widgets([])
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
