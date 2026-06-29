<?php

declare(strict_types=1);

namespace App\Factory\Filament\Resources\FactoryCapabilityResource\Pages;

use App\Factory\Filament\Resources\FactoryCapabilityResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFactoryCapability extends CreateRecord
{
    protected static string $resource = FactoryCapabilityResource::class;
}
