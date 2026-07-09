<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class AtlasAdministracao extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = '02 · Atlas Operacional';
    protected static ?string $navigationLabel = 'Administração';
    protected static ?string $title = 'Administração';
    protected static ?string $slug = 'atlas/administracao';
    protected static ?int $navigationSort = 18;
    protected static string $view = 'filament.pages.atlas-administracao';
}
