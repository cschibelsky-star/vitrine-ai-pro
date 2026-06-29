<?php

declare(strict_types=1);

namespace App\Factory\Filament\Resources\FactoryExecutionResource\Pages;

use App\Factory\Filament\Resources\FactoryExecutionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewFactoryExecution extends ViewRecord
{
    protected static string $resource = FactoryExecutionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
