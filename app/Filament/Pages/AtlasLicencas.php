<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class AtlasLicencas extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-key';
    protected static ?string $navigationGroup = '02 · Atlas Operacional';
    protected static ?string $navigationLabel = 'Licenças';
    protected static ?string $title = 'Licenças';
    protected static ?string $slug = 'atlas/licencas';
    protected static ?int $navigationSort = 11;
    protected static string $view = 'filament.pages.atlas-licencas';
}
