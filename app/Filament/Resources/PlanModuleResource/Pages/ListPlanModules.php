<?php

namespace App\Filament\Resources\PlanModuleResource\Pages;

use App\Filament\Resources\PlanModuleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPlanModules extends ListRecords
{
    protected static string $resource = PlanModuleResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
