<?php

declare(strict_types=1);

namespace App\Factory\Console\Commands;

use App\Factory\Dashboard\Services\ModuleDashboardGenerator;
use Illuminate\Console\Command;
use Throwable;

class FactoryDashboardModuleCommand extends Command
{
    protected $signature = 'factory:dashboard-module {slug : Slug do módulo gerado}';
    protected $description = 'Gera especificação de dashboard para um módulo.';

    public function handle(ModuleDashboardGenerator $generator): int
    {
        try {
            $dashboard = $generator->generate((string) $this->argument('slug'));
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        $this->info('Dashboard do módulo gerado.');
        $this->line('Módulo: ' . $dashboard['module']);
        $this->line('Arquivo: ' . $dashboard['path']);
        $this->line('Cards: ' . count($dashboard['cards']));

        return self::SUCCESS;
    }
}
