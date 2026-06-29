<?php

namespace App\Filament\Resources\AiQueueResource\Pages;

use App\Filament\Resources\AiQueueResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAiQueues extends ListRecords
{
    protected static string $resource = AiQueueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
