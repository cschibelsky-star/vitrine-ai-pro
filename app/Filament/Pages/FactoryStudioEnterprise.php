<?php

namespace App\Filament\Pages;

use App\Factory\FinalMaster\Services\FactoryFinalMasterService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Throwable;

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
    public ?array $lastReport = null;

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
                    try {
                        $report = app(FactoryFinalMasterService::class)->buildAndInstall(
                            request: (string) $data['request'],
                            dryRun: true,
                            force: false,
                            migrate: false,
                        );

                        $this->lastReport = $report;
                        $this->lastStatus = ($report['status'] ?? 'failed') === 'finished'
                            ? 'dry-run concluído'
                            : 'falha';
                        $this->lastOutput = json_encode(
                            $report,
                            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                        ) ?: null;

                        Notification::make()
                            ->title($this->lastStatus === 'dry-run concluído'
                                ? 'Produção simulada com sucesso'
                                : 'Pipeline interrompido')
                            ->body($report['final_note'] ?? null)
                            ->success($this->lastStatus === 'dry-run concluído')
                            ->danger($this->lastStatus !== 'dry-run concluído')
                            ->send();
                    } catch (Throwable $exception) {
                        $this->lastReport = [
                            'status' => 'failed',
                            'failed_stage' => 'studio',
                            'error' => $exception->getMessage(),
                        ];
                        $this->lastStatus = 'falha';
                        $this->lastOutput = $exception->getMessage();

                        Notification::make()
                            ->title('Falha na produção')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
