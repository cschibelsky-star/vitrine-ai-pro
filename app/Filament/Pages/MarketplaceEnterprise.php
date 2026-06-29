<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class MarketplaceEnterprise extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationGroup = '04 · Marketplace';
    protected static ?string $navigationLabel = 'Marketplace';
    protected static ?string $title = 'Marketplace';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.pages.marketplace-enterprise';
}
