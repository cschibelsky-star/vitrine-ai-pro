<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;

class FactoryStudioEnterprise extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?string $navigationGroup = '02 · Factory Studio';
    protected static ?string $navigationLabel = 'Studio';
    protected static ?string $title = 'Factory Studio';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.pages.factory-studio-enterprise';

    public ?string $lastOutput = null;
    public ?string $lastStatus = null;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('build')
                ->label('Nova Solicitação')
                ->icon('heroicon-o-sparkles')
                ->form([
                    Textarea::make('request')
                        ->label('O que deseja construir?')
                        ->default('Quero um sistema para pequenas empresas venderem para o governo')
                        ->required()
                        ->rows(4),
                ])
                ->action(function (array $data): void {
                    $exitCode = Artisan::call('factory:build-and-install', [
                        'request' => [(string) $data['request']],
                        '--dry-run' => true,
                    ]);

                    $this->lastOutput = Artisan::output();
                    $this->lastStatus = $exitCode === 0 ? 'dry-run concluído' : 'falha';

                    Notification::make()
                        ->title($exitCode === 0 ? 'Produção simulada' : 'Falha na produção')
                        ->success($exitCode === 0)
                        ->danger($exitCode !== 0)
                        ->send();
                }),
        ];
    }
}
