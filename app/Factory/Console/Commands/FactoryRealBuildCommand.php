<?php

declare(strict_types=1);

namespace App\Factory\Console\Commands;

use App\Factory\RealBuilder\Services\RealCodeGenerator;
use Illuminate\Console\Command;
use Throwable;

class FactoryRealBuildCommand extends Command
{
    protected $signature = 'factory:real-build {blueprint : Slug do blueprint} {--run-id= : ID único da execução}';

    protected $description = 'Gera código Laravel/Filament real a partir de um blueprint.';

    public function handle(RealCodeGenerator $builder): int
    {
        try {
            $report = $builder->generate((string) $this->argument('blueprint'), $this->option('run-id') ?: null);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        $this->info('Real Build gerado.');
        $this->line('Blueprint: ' . $report['blueprint']);
        $this->line('Arquivos: ' . $report['files_count']);
        $this->line('Relatório: ' . $report['path']);

        return self::SUCCESS;
    }
}
