<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class FactoryStudio extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';

    protected static ?string $navigationGroup = 'Factory 2.0';

    protected static ?string $navigationLabel = 'Studio';

    protected static ?string $title = 'Factory Studio';

    protected static ?int $navigationSort = 20;

    protected static string $view = 'filament.pages.factory-studio';

    public ?string $requestText = 'Quero um sistema para pequenas empresas venderem para o governo';

    public ?string $product = 'gov360';

    public ?string $lastStatus = null;

    public ?string $lastOutput = null;

    public ?string $lastReportPath = null;

    public ?string $productionReportPath = null;

    public function mount(): void
    {
        $this->productionReportPath = storage_path('app/factory/production-enterprise/gov360/production_report.json');

        if (File::exists($this->productionReportPath)) {
            $this->lastReportPath = $this->productionReportPath;
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('produce')
                ->label('Produzir')
                ->icon('heroicon-o-bolt')
                ->color('primary')
                ->form([
                    Textarea::make('requestText')
                        ->label('Descreva o sistema que deseja produzir')
                        ->default($this->requestText)
                        ->required()
                        ->rows(4),
                ])
                ->action(function (array $data): void {
                    $this->requestText = (string) ($data['requestText'] ?? $this->requestText);

                    $exitCode = Artisan::call('factory:produce-request', [
                        'request' => [$this->requestText],
                    ]);

                    $this->lastOutput = Artisan::output();
                    $this->lastStatus = $exitCode === 0 ? 'finished' : 'failed';
                    $this->product = 'gov360';
                    $this->productionReportPath = storage_path('app/factory/production-enterprise/gov360/production_report.json');
                    $this->lastReportPath = $this->productionReportPath;

                    Notification::make()
                        ->title($exitCode === 0 ? 'Produção finalizada' : 'Produção falhou')
                        ->body($exitCode === 0 ? 'A Factory produziu o sistema em modo seguro.' : 'Verifique o retorno da produção.')
                        ->success($exitCode === 0)
                        ->danger($exitCode !== 0)
                        ->send();
                }),

            Action::make('dryRunInstall')
                ->label('Simular instalação GOV360')
                ->icon('heroicon-o-shield-check')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function (): void {
                    $exitCode = Artisan::call('factory:install-system', [
                        'product' => 'gov360',
                        '--dry-run' => true,
                    ]);

                    $this->lastOutput = Artisan::output();
                    $this->lastStatus = $exitCode === 0 ? 'install_dry_run_passed' : 'install_dry_run_failed';

                    Notification::make()
                        ->title($exitCode === 0 ? 'Simulação aprovada' : 'Simulação falhou')
                        ->body($exitCode === 0 ? 'Todos os módulos passaram no dry-run.' : 'Verifique o retorno da simulação.')
                        ->success($exitCode === 0)
                        ->danger($exitCode !== 0)
                        ->send();
                }),

            Action::make('refreshReport')
                ->label('Atualizar relatório')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function (): void {
                    $this->productionReportPath = storage_path('app/factory/production-enterprise/gov360/production_report.json');
                    $this->lastReportPath = File::exists($this->productionReportPath) ? $this->productionReportPath : null;

                    Notification::make()
                        ->title('Relatório atualizado')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function getProductionReport(): ?array
    {
        $path = storage_path('app/factory/production-enterprise/gov360/production_report.json');

        if (! File::exists($path)) {
            return null;
        }

        return json_decode((string) File::get($path), true);
    }

    public function getBuildModules(): array
    {
        $report = $this->getProductionReport();

        return $report['modules'] ?? [];
    }
}
