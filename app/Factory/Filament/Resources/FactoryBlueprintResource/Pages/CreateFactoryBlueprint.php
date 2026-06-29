<?php

declare(strict_types=1);

namespace App\Factory\Filament\Resources\FactoryBlueprintResource\Pages;

use App\Factory\Filament\Resources\FactoryBlueprintResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFactoryBlueprint extends CreateRecord
{
    protected static string $resource = FactoryBlueprintResource::class;
}
