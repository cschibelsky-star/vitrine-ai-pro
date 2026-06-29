<?php

declare(strict_types=1);

namespace App\Factory\Console\Commands;

use App\Factory\EnterpriseMaturity\Services\EnterpriseCodeGenerator;
use Illuminate\Console\Command;
use Throwable;

class FactoryEnterpriseBuildCommand extends Command
{
    protected $signature = 'factory:enterprise-build {blueprint : Slug do blueprint}';

    protected $description = 'Gera camadas enterprise para um blueprint produzido.';

    public function handle(EnterpriseCodeGenerator $generator): int
    {
        try {
            $report = $generator->generate((string) $this->argument('blueprint'));
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        $this->info('Enterprise Build gerado.');
        $this->line('Blueprint: ' . $report['blueprint']);
        $this->line('Arquivos: ' . $report['files_count']);
        $this->line('Relatório: ' . $report['path']);

        return self::SUCCESS;
    }
}
