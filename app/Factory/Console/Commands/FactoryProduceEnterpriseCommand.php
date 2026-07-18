<?php

declare(strict_types=1);

namespace App\Factory\Console\Commands;

use App\Factory\ProductionEnterprise\Services\EnterpriseProductionEngine;
use Illuminate\Console\Command;
use Throwable;

class FactoryProduceEnterpriseCommand extends Command
{
    protected $signature = 'factory:produce-enterprise {product : Produto a produzir, ex: gov360}';

    protected $description = 'Executa explicitamente o pipeline Enterprise, sem disputar o comando canônico.';

    public function handle(EnterpriseProductionEngine $engine): int
    {
        try {
            $report = $engine->produce((string) $this->argument('product'));
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        $this->info('Factory Production finalizada.');
        $this->line('Produto: ' . $report['product_name']);
        $this->line('Status: ' . $report['status']);
        $this->line('Modo: ' . $report['mode']);
        $this->line('Release: ' . $report['release']);
        $this->line('Relatório: ' . $report['path']);
        $this->line('Builds: ' . $report['builds_path']);

        $this->table(['Etapa', 'Arquivo'], array_map(
            fn ($step, $path) => [$step, $path],
            array_keys($report['steps']),
            $report['steps']
        ));

        return $report['status'] === 'finished' ? self::SUCCESS : self::FAILURE;
    }
}
