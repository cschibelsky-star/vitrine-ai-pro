<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class AtlasFinanceiro extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = '02 · Atlas Operacional';
    protected static ?string $navigationLabel = 'Financeiro';
    protected static ?string $title = 'Financeiro';
    protected static ?string $slug = 'atlas/financeiro';
    protected static ?int $navigationSort = 15;
    protected static string $view = 'filament.pages.atlas-financeiro';
}
