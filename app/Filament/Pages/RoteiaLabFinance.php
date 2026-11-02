<?php

namespace App\Filament\Pages;

use App\Services\RoteiaLab\RoteiaLabFinanceReader;
use Filament\Pages\Page;

final class RoteiaLabFinance extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = '05 · Financeiro';
    protected static ?string $navigationLabel = 'Roteia AI Lab';
    protected static ?string $title = 'Roteia AI Lab · Consulta financeira';
    protected static ?string $slug = 'roteia-lab-financeiro';
    protected static ?int $navigationSort = 30;
    protected static string $view = 'filament.pages.roteia-lab-finance';

    public function finance(): array
    {
        return app(RoteiaLabFinanceReader::class)->snapshot();
    }
}
