<?php

declare(strict_types=1);

namespace App\Factory\Filament\Resources\FactoryExecutionLogResource\Pages;

use App\Factory\Filament\Resources\FactoryExecutionLogResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFactoryExecutionLog extends CreateRecord
{
    protected static string $resource = FactoryExecutionLogResource::class;
}
