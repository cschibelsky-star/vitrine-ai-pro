<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class MarketplaceEnterprise extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;
    protected static ?string $navigationLabel = 'Marketplace';
    protected static ?string $title = 'Marketplace';
    protected static string|\UnitEnum|null $navigationGroup = 'Factory';
    protected static ?int $navigationSort = 4;
    protected string $view = 'filament.pages.marketplace-enterprise';
}
