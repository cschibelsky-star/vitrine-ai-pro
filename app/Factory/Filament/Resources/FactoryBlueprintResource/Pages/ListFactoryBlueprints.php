<?php

declare(strict_types=1);

namespace App\Factory\Filament\Resources\FactoryBlueprintResource\Pages;

use App\Factory\Filament\Resources\FactoryBlueprintResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFactoryBlueprints extends ListRecords
{
    protected static string $resource = FactoryBlueprintResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
