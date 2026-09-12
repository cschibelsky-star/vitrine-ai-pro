<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class AtlasProdutos extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationGroup = '02 · Atlas Operacional';
    protected static ?string $navigationLabel = 'Produtos';
    protected static ?string $title = 'Produtos';
    protected static ?string $slug = 'atlas/produtos';
    protected static ?int $navigationSort = 12;
    protected static string $view = 'filament.pages.atlas-produtos';
}
