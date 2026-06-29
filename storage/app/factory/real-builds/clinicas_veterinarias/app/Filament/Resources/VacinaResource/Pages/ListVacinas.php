<?php

namespace App\Filament\Resources\VacinaResource\Pages;

use App\Filament\Resources\VacinaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVacinas extends ListRecords
{
    protected static string $resource = VacinaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
