<?php

declare(strict_types=1);

namespace App\Factory\Filament\Resources\FactoryExecutionResource\Pages;

use App\Factory\Filament\Resources\FactoryExecutionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFactoryExecution extends EditRecord
{
    protected static string $resource = FactoryExecutionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
