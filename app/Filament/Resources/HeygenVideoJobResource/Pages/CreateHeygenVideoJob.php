<?php

namespace App\Filament\Resources\HeygenVideoJobResource\Pages;

use App\Filament\Resources\HeygenVideoJobResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHeygenVideoJob extends CreateRecord
{
    protected static string $resource = HeygenVideoJobResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
