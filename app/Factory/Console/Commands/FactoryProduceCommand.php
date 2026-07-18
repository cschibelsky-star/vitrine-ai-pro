<?php

declare(strict_types=1);

namespace App\Factory\Console\Commands;

use App\Factory\Production\Services\FactoryProductionCoordinator;
use Illuminate\Console\Command;
use Throwable;

class FactoryProduceCommand extends Command
{
    protected $signature = 'factory:produce
        {product : Produto a produzir, ex: gov360}
        {--pipeline= : Pipeline explícito: classic ou enterprise}
        {--idempotency-key= : Chave estável para impedir produção duplicada}';
    protected $description = 'Executa o pipeline canônico da Factory com lock, estado e idempotência.';

    public function handle(FactoryProductionCoordinator $coordinator): int
    {
        try {
            $report = $coordinator->produce(
                (string) $this->argument('product'),
                $this->option('pipeline') ? (string) $this->option('pipeline') : null,
                $this->option('idempotency-key') ? (string) $this->option('idempotency-key') : null,
            );
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        $this->info('Factory Production concluída.');
        $this->line('Produto: ' . $report['product_name']);
        $this->line('Run ID: ' . $report['run_id']);
        $this->line('Pipeline: ' . $report['pipeline']);
        $this->line('Status: ' . $report['status']);
        $this->line('Modo: ' . $report['mode']);
        $this->line('Relatório: ' . $report['path']);
        $this->line('Coordenador: ' . $report['coordinator_report_path']);

        $this->table(['Etapa', 'Arquivo'], array_map(fn ($step, $path) => [$step, $path], array_keys($report['steps']), $report['steps']));

        return $report['status'] === 'finished' ? self::SUCCESS : self::FAILURE;
    }
}
