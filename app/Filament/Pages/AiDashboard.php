<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class AiDashboard extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'AI Dashboard';

    protected static string $view = 'filament.pages.ai-dashboard';
}
