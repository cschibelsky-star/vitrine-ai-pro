<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class AtlasIaCenter extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?string $navigationGroup = '02 · Atlas Operacional';
    protected static ?string $navigationLabel = 'IA Center';
    protected static ?string $title = 'IA Center';
    protected static ?string $slug = 'atlas/ia-center';
    protected static ?int $navigationSort = 14;
    protected static string $view = 'filament.pages.atlas-ia-center';
}
