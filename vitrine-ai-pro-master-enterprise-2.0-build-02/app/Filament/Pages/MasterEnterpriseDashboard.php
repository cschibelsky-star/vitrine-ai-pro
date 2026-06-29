<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class MasterEnterpriseDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Dashboard Executivo';
    protected static ?string $title = 'Centro Operacional Master';
    protected static ?string $navigationGroup = 'Dashboard';
    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.master-enterprise-dashboard';
}
