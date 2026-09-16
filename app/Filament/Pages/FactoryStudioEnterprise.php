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
            Action::make('buildDryRun')
                ->label('Simular Solicitação')
                ->icon('heroicon-o-beaker')
                ->form([
                    Textarea::make('request')
                        ->label('O que deseja construir?')
                        ->default('Quero um sistema para pequenas empresas venderem para o governo')
                        ->required()
                        ->rows(4),
                ])
                ->action(function (array $data): void {
                    $this->runFactoryBuild($data, true);
                }),

            Action::make('buildReal')
                ->label('Produzir de Verdade')
                ->icon('heroicon-o-rocket-launch')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Confirmar produção real')
                ->modalDescription('Esta ação executa o pipeline real da Factory e materializa a produção. Migrations permanecem desativadas.')
                ->modalSubmitActionLabel('EXECUTAR PRODUÇÃO REAL')
                ->form([
                    Textarea::make('request')
                        ->label('O que deseja construir?')
                        ->required()
                        ->rows(4),
                ])
                ->action(function (array $data): void {
                    $this->runFactoryBuild($data, false);
                }),
        ];
    }

    protected function runFactoryBuild(array $data, bool $dryRun): void
    {
        try {
            $report = app(FactoryFinalMasterService::class)->buildAndInstall(
                request: (string) $data['request'],
                dryRun: $dryRun,
                force: false,
                migrate: false,
            );

            $finished = ($report['status'] ?? 'failed') === 'finished';
            $this->lastReport = $report;
            $this->lastStatus = $finished
                ? ($dryRun ? 'dry-run concluído' : 'produção real concluída')
                : 'falha';
            $this->lastOutput = json_encode(
                $report,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ) ?: null;

            Notification::make()
                ->title($finished
                    ? ($dryRun ? 'Produção simulada com sucesso' : 'Produção real concluída com sucesso')
                    : 'Pipeline interrompido')
                ->body($report['final_note'] ?? null)
                ->success($finished)
                ->danger(! $finished)
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
    }
}
