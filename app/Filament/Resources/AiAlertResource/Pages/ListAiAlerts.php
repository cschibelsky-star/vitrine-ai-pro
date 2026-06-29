<?php

namespace App\Filament\Resources\AiAlertResource\Pages;

use App\Filament\Resources\AiAlertResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAiAlerts extends ListRecords
{
    protected static string $resource = AiAlertResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
