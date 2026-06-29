<?php

namespace App\Filament\Resources\ProntuarioResource\Pages;

use App\Filament\Resources\ProntuarioResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProntuarios extends ListRecords
{
    protected static string $resource = ProntuarioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
