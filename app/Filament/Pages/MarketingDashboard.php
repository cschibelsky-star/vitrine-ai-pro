<?php

namespace App\Filament\Pages;

use App\Services\Marketing\MarketingDashboardClient;
use Filament\Pages\Page;

class MarketingDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    protected static ?string $navigationGroup = '10 · IA Center';
    protected static ?string $navigationLabel = 'Marketing IA';
    protected static ?string $title = 'Marketing IA';
    protected static ?int $navigationSort = 2;
    protected static string $view = 'filament.pages.marketing-dashboard';

    /** @return array<string, mixed> */
    public function getDashboardData(): array
    {
        return app(MarketingDashboardClient::class)->fetch();
    }
}
