<?php

declare(strict_types=1);

namespace App\Factory\Console\Commands;

use App\Factory\Builder\Services\ModuleQaService;
use Illuminate\Console\Command;

class FactoryQaModuleCommand extends Command
{
    protected $signature = 'factory:qa-module {slug}';
    protected $description = 'Executa QA em módulo gerado pela Factory.';

    public function handle(ModuleQaService $qa): int
    {
        $report = $qa->inspect((string) $this->argument('slug'));

        $this->line('Status: ' . $report['status']);

        $rows = [];
        foreach ($report['checks'] ?? [] as $check) {
            $rows[] = [$check['status'], $check['file'] ?? '-', $check['message'] ?? '-'];
        }

        $this->table(['Status', 'Arquivo', 'Mensagem'], $rows);

        return $report['status'] === 'passed' ? self::SUCCESS : self::FAILURE;
    }
}
