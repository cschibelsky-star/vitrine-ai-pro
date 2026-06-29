<?php

declare(strict_types=1);

namespace App\Factory\Console\Commands;

use App\Factory\Production\Services\FactoryProductionOrchestrator;
use Illuminate\Console\Command;
use Throwable;

class FactoryProduceCommand extends Command
{
    protected $signature = 'factory:produce {product : Produto a produzir, ex: gov360}';
    protected $description = 'Produz um pacote técnico completo de produto usando a Factory.';

    public function handle(FactoryProductionOrchestrator $orchestrator): int
    {
        try {
            $report = $orchestrator->produce((string) $this->argument('product'));
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        $this->info('Factory Production concluída.');
        $this->line('Produto: ' . $report['product_name']);
        $this->line('Status: ' . $report['status']);
        $this->line('Modo: ' . $report['mode']);
        $this->line('Relatório: ' . $report['path']);

        $this->table(['Etapa', 'Arquivo'], array_map(fn ($step, $path) => [$step, $path], array_keys($report['steps']), $report['steps']));

        return $report['status'] === 'finished' ? self::SUCCESS : self::FAILURE;
    }
}
