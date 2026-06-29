<?php

declare(strict_types=1);

namespace App\Factory\Console\Commands;

use App\Factory\FinalProducer\Services\SystemInstallPlanner;
use Illuminate\Console\Command;
use Throwable;

class FactoryInstallSystemCommand extends Command
{
    protected $signature = 'factory:install-system {product : Produto produzido, ex: gov360} {--dry-run : Apenas simula a instalação} {--force : Força sobrescrita quando suportado}';
    protected $description = 'Instala ou simula a instalação de todos os módulos de um sistema produzido.';

    public function handle(SystemInstallPlanner $planner): int
    {
        try {
            $report = $planner->plan((string) $this->argument('product'), (bool) $this->option('dry-run'), (bool) $this->option('force'));
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        $this->info('Factory Install System finalizado.');
        $this->line('Produto: ' . $report['product']);
        $this->line('Modo: ' . $report['mode']);
        $this->line('Status: ' . $report['status']);
        $this->line('Relatório: ' . $report['path']);

        $this->table(['Módulo', 'Status'], array_map(fn ($item) => [$item['module'], $item['status']], $report['results']));

        return $report['status'] === 'passed' ? self::SUCCESS : self::FAILURE;
    }
}
