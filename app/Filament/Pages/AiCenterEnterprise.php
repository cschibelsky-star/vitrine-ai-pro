<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class AiCenterEnterprise extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationGroup = '06 · IA Center';
    protected static ?string $navigationLabel = 'Agentes';
    protected static ?string $title = 'IA Center';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.pages.ai-center-enterprise';
}
