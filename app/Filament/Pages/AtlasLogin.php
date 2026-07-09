<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class AtlasLogin extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';
    protected static ?string $navigationGroup = '02 · Atlas Operacional';
    protected static ?string $navigationLabel = 'Login';
    protected static ?string $title = 'Login';
    protected static ?string $slug = 'atlas/login';
    protected static ?int $navigationSort = 19;
    protected static string $view = 'filament.pages.atlas-login';
}
