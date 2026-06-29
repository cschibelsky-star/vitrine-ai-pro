<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class FactoryDashboard extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCpuChip;
    protected static ?string $navigationLabel = 'Factory Dashboard';
    protected static ?string $title = 'Factory Core';
    protected static string|\UnitEnum|null $navigationGroup = 'Factory';
    protected static ?int $navigationSort = 1;
    protected string $view = 'filament.pages.factory-dashboard';
}
