<?php

declare(strict_types=1);

namespace App\Factory\Console\Commands;

use App\Factory\QA\Services\SmartQa2Service;
use Illuminate\Console\Command;

class FactorySmartQa2Command extends Command
{
    protected $signature = 'factory:smart-qa2';
    protected $description = 'Executa Smart QA 2.0 da Factory v3.0.';

    public function handle(SmartQa2Service $qa): int
    {
        $report = $qa->inspect();

        $this->info('Smart QA 2.0');
        $this->line('Status: ' . $report['status']);

        $this->table(['Status', 'Check', 'Mensagem'], array_map(fn ($check) => [
            $check['status'],
            $check['key'],
            $check['message'],
        ], $report['checks']));

        return $report['status'] === 'passed' ? self::SUCCESS : self::FAILURE;
    }
}
