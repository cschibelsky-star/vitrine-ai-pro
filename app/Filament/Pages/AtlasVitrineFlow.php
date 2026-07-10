<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class AtlasVitrineFlow extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-bolt';
    protected static ?string $navigationGroup = '02 · Atlas Operacional';
    protected static ?string $navigationLabel = 'Vitrine IA Flow';
    protected static ?string $title = 'Vitrine IA Flow';
    protected static ?string $slug = 'atlas/vitrine-flow';
    protected static ?int $navigationSort = 20;
    protected static string $view = 'filament.pages.atlas-vitrine-flow';
}
