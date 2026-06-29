<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class EnterpriseDashboard extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Dashboard Executivo';

    protected static string $view = 'filament.pages.enterprise-dashboard';
}
