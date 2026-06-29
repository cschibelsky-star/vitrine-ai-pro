<?php

declare(strict_types=1);

namespace App\Factory\Console\Commands;

use App\Factory\EnterpriseMaturity\Services\EnterpriseBuildInstaller;
use Illuminate\Console\Command;
use Throwable;

class FactoryEnterpriseInstallCommand extends Command
{
    protected $signature = 'factory:enterprise-install {blueprint : Slug do blueprint} {--dry-run : Simula a instalação} {--force : Sobrescreve arquivos existentes com backup}';

    protected $description = 'Instala camadas enterprise geradas pela Factory.';

    public function handle(EnterpriseBuildInstaller $installer): int
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

        $this->info('Enterprise Install finalizado.');
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
