<?php

declare(strict_types=1);

namespace App\Factory\Console\Commands;

use App\Factory\RealBuilder\Services\RealBuildInstaller;
use Illuminate\Console\Command;
use Throwable;

class FactoryRealInstallCommand extends Command
{
    protected $signature = 'factory:real-install {blueprint : Slug do blueprint} {--dry-run : Simula a instalação} {--force : Sobrescreve arquivos existentes com backup}';

    protected $description = 'Instala código real gerado pela Factory.';

    public function handle(RealBuildInstaller $installer): int
    {
        try {
            $report = $installer->install(
                (string) $this->argument('blueprint'),
                (bool) $this->option('dry-run'),
                (bool) $this->option('force'),
            );
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        $this->info('Real Install finalizado.');
        $this->line('Blueprint: ' . $report['blueprint']);
        $this->line('Modo: ' . $report['mode']);
        $this->line('Arquivos: ' . $report['files']);
        $this->line('Relatório: ' . $report['path']);

        $this->table(['Status', 'Destino'], array_map(fn ($item) => [
            $item['status'],
            $item['destination'],
        ], array_slice($report['results'], 0, 20)));

        return self::SUCCESS;
    }
}
