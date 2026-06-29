<?php

declare(strict_types=1);

namespace App\Factory\Filament\Resources\FactoryExecutionLogResource\Pages;

use App\Factory\Filament\Resources\FactoryExecutionLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewFactoryExecutionLog extends ViewRecord
{
    protected static string $resource = FactoryExecutionLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
