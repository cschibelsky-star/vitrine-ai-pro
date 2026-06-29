<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class MarketplaceEnterprise extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationGroup = 'Factory 2.0';
    protected static ?string $navigationLabel = 'Marketplace';
    protected static ?string $title = 'Marketplace';
    protected static ?int $navigationSort = 30;
    protected static string $view = 'filament.pages.marketplace-enterprise';
}
