<?php

namespace App\Filament\Resources\AiConsumptionResource\Pages;

use App\Filament\Resources\AiConsumptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAiConsumptions extends ListRecords
{
    protected static string $resource = AiConsumptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
