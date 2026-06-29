<?php

declare(strict_types=1);

namespace App\Factory\Console\Commands;

use App\Factory\Dashboard\Services\ExecutiveDashboardGenerator;
use Illuminate\Console\Command;
use Throwable;

class FactoryExecutiveDashboardCommand extends Command
{
    protected $signature = 'factory:executive-dashboard {slug : Slug do blueprint do sistema}';
    protected $description = 'Gera dashboard executivo para um sistema criado por blueprint.';

    public function handle(ExecutiveDashboardGenerator $generator): int
    {
        try {
            $dashboard = $generator->generate((string) $this->argument('slug'));
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        $this->info('Dashboard executivo gerado.');
        $this->line('Sistema: ' . $dashboard['system']);
        $this->line('Arquivo: ' . $dashboard['path']);
        $this->line('Cards: ' . count($dashboard['cards']));
        $this->line('Charts: ' . count($dashboard['charts']));
        $this->line('Alertas: ' . count($dashboard['alerts']));

        return self::SUCCESS;
    }
}
