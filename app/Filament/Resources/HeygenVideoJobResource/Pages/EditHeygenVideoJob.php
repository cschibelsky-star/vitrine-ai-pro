<?php

namespace App\Filament\Resources\HeygenVideoJobResource\Pages;

use App\Filament\Resources\HeygenVideoJobResource;
use App\Services\Heygen\HeygenService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditHeygenVideoJob extends EditRecord
{
    protected static string $resource = HeygenVideoJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('gerar')->label('Gerar Agora')->icon('heroicon-o-bolt')->requiresConfirmation()->action(function () {
                app(HeygenService::class)->generateVideo($this->record);
                Notification::make()->title('Job enviado para HeyGen')->success()->send();
                $this->refreshFormData(['status','heygen_video_id','response','error_message']);
            }),
            Actions\Action::make('atualizar')->label('Atualizar Status')->icon('heroicon-o-arrow-path')->visible(fn () => filled($this->record->heygen_video_id))->action(function () {
                app(HeygenService::class)->refreshStatus($this->record);
                Notification::make()->title('Status atualizado')->success()->send();
                $this->refreshFormData(['status','video_url','thumbnail_url','duration_seconds','credits_used','response','error_message']);
            }),
            Actions\Action::make('assistir')->label('Assistir vídeo')->icon('heroicon-o-play')->url(fn () => $this->record->video_url ?: '#', true)->visible(fn () => filled($this->record->video_url)),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
