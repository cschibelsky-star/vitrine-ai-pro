<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class AtlasEmpresa extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-identification';
    protected static ?string $navigationGroup = '02 · Atlas Operacional';
    protected static ?string $navigationLabel = 'Empresa';
    protected static ?string $title = 'Empresa';
    protected static ?string $slug = 'atlas/empresa';
    protected static ?int $navigationSort = 17;
    protected static string $view = 'filament.pages.atlas-empresa';
}
