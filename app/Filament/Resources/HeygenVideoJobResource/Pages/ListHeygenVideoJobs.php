<?php

namespace App\Filament\Resources\HeygenVideoJobResource\Pages;

use App\Filament\Resources\HeygenVideoJobResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHeygenVideoJobs extends ListRecords
{
    protected static string $resource = HeygenVideoJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Criar Vídeo HeyGen'),
        ];
    }
}
