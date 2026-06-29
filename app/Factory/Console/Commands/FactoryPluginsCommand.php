<?php

declare(strict_types=1);

namespace App\Factory\Console\Commands;

use App\Factory\Core\Services\FactoryPluginRegistry;
use Illuminate\Console\Command;

class FactoryPluginsCommand extends Command
{
    protected $signature = 'factory:plugins';

    protected $description = 'Lista os plugins/capacidades registradas da Factory.';

    public function handle(FactoryPluginRegistry $registry): int
    {
        $summary = $registry->summary();

        $this->info('Factory Plugin Registry');
        $this->line('Total: ' . $summary['total']);
        $this->line('Ativos: ' . $summary['active']);

        $rows = [];

        foreach ($summary['plugins'] as $key => $plugin) {
            $rows[] = [
                $key,
                $plugin['name'] ?? '-',
                $plugin['version'] ?? '-',
                $plugin['status'] ?? '-',
                $plugin['description'] ?? '-',
            ];
        }

        $this->table(['Key', 'Plugin', 'Versão', 'Status', 'Descrição'], $rows);

        return self::SUCCESS;
    }
}
