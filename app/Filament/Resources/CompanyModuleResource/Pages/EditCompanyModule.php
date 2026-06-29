<?php

namespace App\Filament\Resources\CompanyModuleResource\Pages;

use App\Filament\Resources\CompanyModuleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCompanyModule extends EditRecord
{
    protected static string $resource = CompanyModuleResource::class;
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
}
