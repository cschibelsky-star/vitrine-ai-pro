<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class AtlasClientes extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationGroup = '02 · Atlas Operacional';
    protected static ?string $navigationLabel = 'Clientes';
    protected static ?string $title = 'Clientes';
    protected static ?string $slug = 'atlas/clientes';
    protected static ?int $navigationSort = 10;
    protected static string $view = 'filament.pages.atlas-clientes';
}
