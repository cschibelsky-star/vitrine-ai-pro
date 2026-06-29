<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class ClientPortalEnterprise extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    protected static ?string $navigationGroup = '05 · Portal do Cliente';
    protected static ?string $navigationLabel = 'Portal';
    protected static ?string $title = 'Portal do Cliente';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.pages.client-portal-enterprise';
}
