<?php

declare(strict_types=1);

namespace App\Factory\Filament\Resources\FactoryProjectResource\Pages;

use App\Factory\Filament\Resources\FactoryProjectResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFactoryProject extends CreateRecord
{
    protected static string $resource = FactoryProjectResource::class;
}
