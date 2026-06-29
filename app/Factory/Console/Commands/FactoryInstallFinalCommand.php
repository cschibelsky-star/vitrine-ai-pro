<?php

declare(strict_types=1);

namespace App\Factory\Console\Commands;

use App\Factory\Finalization\Services\SmartFinalInstallerService;
use Illuminate\Console\Command;
use Throwable;

class FactoryInstallFinalCommand extends Command
{
    protected $signature = 'factory:install-final {blueprint : Slug do blueprint/sistema} {--dry-run : Simula a instalação} {--force : Força instalação quando suportado} {--migrate : Executa migrations ao final, apenas fora do dry-run}';
    protected $description = 'Instala ou simula a instalação final de um sistema produzido.';

    public function handle(SmartFinalInstallerService $installer): int
    {
        try {
            $report = $installer->install(
                (string) $this->argument('blueprint'),
                (bool) $this->option('dry-run'),
                (bool) $this->option('force'),
                (bool) $this->option('migrate'),
            );
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        $this->info('Factory Install Final concluído.');
        $this->line('Blueprint: ' . $report['blueprint']);
        $this->line('Modo: ' . $report['mode']);
        $this->line('Status: ' . $report['status']);
        $this->line('Relatório: ' . $report['path']);

        $this->table(['Módulo', 'Status', 'Comando'], array_map(fn ($item) => [
            $item['module'],
            $item['status'],
            $item['command'],
        ], $report['results']));

        return $report['status'] === 'passed' ? self::SUCCESS : self::FAILURE;
    }
}
