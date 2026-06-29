<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class FactoryEnterpriseDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?string $navigationLabel = 'Factory Dashboard';
    protected static ?string $title = 'Factory 2.0';
    protected static ?string $navigationGroup = 'Factory';
    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.factory-enterprise-dashboard';
}
