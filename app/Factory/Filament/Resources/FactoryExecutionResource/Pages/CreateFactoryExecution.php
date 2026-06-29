<?php

declare(strict_types=1);

namespace App\Factory\Filament\Resources\FactoryExecutionResource\Pages;

use App\Factory\Filament\Resources\FactoryExecutionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFactoryExecution extends CreateRecord
{
    protected static string $resource = FactoryExecutionResource::class;
}
