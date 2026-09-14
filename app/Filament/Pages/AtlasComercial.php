<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class AtlasComercial extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationGroup = '03 · Comercial';
    protected static ?string $navigationLabel = 'Visão Geral';
    protected static ?string $title = 'Comercial';
    protected static ?string $slug = 'atlas/comercial';
    protected static ?int $navigationSort = 16;
    protected static string $view = 'filament.pages.atlas-comercial';
}
