<?php

declare(strict_types=1);

namespace App\Factory\Console\Commands;

use App\Factory\Learning\Services\SmartQaService;
use Illuminate\Console\Command;

class FactorySmartQaCommand extends Command
{
    protected $signature = 'factory:smart-qa {slug : Slug do módulo gerado}';
    protected $description = 'Executa Smart QA em um módulo gerado pela Factory.';

    public function handle(SmartQaService $qa): int
    {
        $report = $qa->inspect((string) $this->argument('slug'));

        $this->info('Smart QA');
        $this->line('Módulo: ' . $report['module']);
        $this->line('Status: ' . $report['status']);

        $this->table(['Status', 'Check', 'Mensagem'], array_map(fn ($check) => [
            $check['status'],
            $check['name'],
            $check['message'],
        ], $report['checks']));

        return $report['status'] === 'passed' ? self::SUCCESS : self::FAILURE;
    }
}
