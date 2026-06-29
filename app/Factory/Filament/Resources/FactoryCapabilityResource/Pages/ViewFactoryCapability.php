<?php

declare(strict_types=1);

namespace App\Factory\Filament\Resources\FactoryCapabilityResource\Pages;

use App\Factory\Filament\Resources\FactoryCapabilityResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewFactoryCapability extends ViewRecord
{
    protected static string $resource = FactoryCapabilityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
