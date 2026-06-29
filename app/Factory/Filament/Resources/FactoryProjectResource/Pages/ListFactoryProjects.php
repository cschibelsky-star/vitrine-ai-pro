<?php

declare(strict_types=1);

namespace App\Factory\Filament\Resources\FactoryProjectResource\Pages;

use App\Factory\Filament\Resources\FactoryProjectResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFactoryProjects extends ListRecords
{
    protected static string $resource = FactoryProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
