<?php

namespace App\Filament\Resources\AiExecutionResource\Pages;

use App\Filament\Resources\AiExecutionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAiExecutions extends ListRecords
{
    protected static string $resource = AiExecutionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
