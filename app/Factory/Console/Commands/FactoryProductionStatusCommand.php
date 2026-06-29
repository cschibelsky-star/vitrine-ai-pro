<?php

declare(strict_types=1);

namespace App\Factory\Console\Commands;

use App\Factory\Production\Services\ProductionStatusService;
use Illuminate\Console\Command;

class FactoryProductionStatusCommand extends Command
{
    protected $signature = 'factory:production-status';
    protected $description = 'Exibe o status do Factory Production Engine.';

    public function handle(ProductionStatusService $service): int
    {
        $status = $service->status();

        $this->info('Factory Production Engine');
        $this->line('Engine: ' . $status['engine']);
        $this->line('Version: ' . $status['version']);
        $this->line('Status: ' . $status['status']);
        $this->line('Storage: ' . ($status['storage_ready'] ? 'ready' : 'failed'));

        $this->table(['Produtos disponíveis'], array_map(fn ($item) => [$item], $status['products_available']));

        return self::SUCCESS;
    }
}
