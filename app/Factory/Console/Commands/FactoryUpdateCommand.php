<?php

declare(strict_types=1);

namespace App\Factory\Console\Commands;

use App\Factory\Core\Services\FactoryUpdateService;
use Illuminate\Console\Command;

class FactoryUpdateCommand extends Command
{
    protected $signature = 'factory:update';

    protected $description = 'Executa atualização/verificação consolidada da Factory.';

    public function handle(FactoryUpdateService $service): int
    {
        $report = $service->run();

        $this->info('Factory Update');
        $this->line('Status: ' . $report['status']);

        $this->table(['Status', 'Check', 'Mensagem'], array_map(fn ($check) => [
            $check['status'],
            $check['name'],
            $check['message'],
        ], $report['checks']));

        $this->newLine();
        $this->warn('Comandos recomendados após update:');
        $this->line('composer dump-autoload');
        $this->line('php artisan optimize:clear');
        $this->line('php artisan filament:clear-cached-components');

        return $report['status'] === 'passed' ? self::SUCCESS : self::FAILURE;
    }
}
