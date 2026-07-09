<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class AtlasFactory extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = '02 · Atlas Operacional';
    protected static ?string $navigationLabel = 'Factory';
    protected static ?string $title = 'Factory';
    protected static ?string $slug = 'atlas/factory';
    protected static ?int $navigationSort = 13;
    protected static string $view = 'filament.pages.atlas-factory';
}
