<?php

declare(strict_types=1);

namespace App\Factory\Filament\Resources\FactoryCapabilityResource\Pages;

use App\Factory\Filament\Resources\FactoryCapabilityResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFactoryCapabilities extends ListRecords
{
    protected static string $resource = FactoryCapabilityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
