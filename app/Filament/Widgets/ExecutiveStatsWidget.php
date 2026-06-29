<?php

namespace App\Filament\Widgets;

use App\Models\Company;
use App\Models\Contract;
use App\Models\Lead;
use App\Models\License;
use App\Models\Module;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ExecutiveStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $leads = class_exists(Lead::class) ? Lead::query()->count() : 0;
        $companies = class_exists(Company::class) ? Company::query()->count() : 0;
        $contracts = class_exists(Contract::class) ? Contract::query()->count() : 0;
        $licenses = class_exists(License::class) ? License::query()->count() : 0;
        $modules = class_exists(Module::class) ? Module::query()->count() : 0;

        return [
            Stat::make('Leads recebidos', $leads)
                ->description('Total captado pelo site, landing e comercial')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('info'),

            Stat::make('Clientes / Instâncias', $companies)
                ->description('Empresas, cidades, portais e operações')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('success'),

            Stat::make('Contratos / Propostas', $contracts)
                ->description('Propostas, contratos e implantações')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('warning'),

            Stat::make('Licenças', $licenses)
                ->description('Licenças cadastradas no ecossistema')
                ->descriptionIcon('heroicon-m-key')
                ->color('primary'),

            Stat::make('Módulos cadastrados', $modules)
                ->description('Funcionalidades controladas pelo Master')
                ->descriptionIcon('heroicon-m-cube-transparent')
                ->color('gray'),
        ];
    }
}
