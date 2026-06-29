<?php

namespace App\Filament\Resources\CompanyModuleResource\Pages;

use App\Filament\Resources\CompanyModuleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCompanyModules extends ListRecords
{
    protected static string $resource = CompanyModuleResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
