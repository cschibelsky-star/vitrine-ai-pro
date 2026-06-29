<?php

declare(strict_types=1);

namespace App\Factory\Filament\Resources\FactoryCapabilityResource\Pages;

use App\Factory\Filament\Resources\FactoryCapabilityResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFactoryCapability extends EditRecord
{
    protected static string $resource = FactoryCapabilityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
