<?php

declare(strict_types=1);

namespace App\Factory\Console\Commands;

use App\Factory\Finalization\Services\FinalizationProductionService;
use Illuminate\Console\Command;
use Throwable;

class FactoryFinalizeRequestCommand extends Command
{
    protected $signature = 'factory:finalize-request {request* : Solicitação livre do sistema completo}';
    protected $description = 'Executa a produção final de uma solicitação livre.';

    public function handle(FinalizationProductionService $producer): int
    {
        $request = implode(' ', (array) $this->argument('request'));

        try {
            $report = $producer->produce($request);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        $this->info('Factory Finalization concluída.');
        $this->line('Status: ' . $report['status']);
        $this->line('Domínio: ' . $report['domain']);
        $this->line('Blueprint: ' . $report['blueprint_slug']);
        $this->line('Relatório: ' . $report['path']);
        $this->warn('Próximo comando: ' . $report['next_command']);

        $this->table(['Módulo'], array_map(fn ($module) => [$module], $report['modules']));

        return $report['status'] === 'finished' ? self::SUCCESS : self::FAILURE;
    }
}
