<?php

declare(strict_types=1);

namespace App\Factory\Console\Commands;

use App\Factory\FinalProducer\Services\ProduceRequestPipeline;
use Illuminate\Console\Command;
use Throwable;

class FactoryProduceRequestCommand extends Command
{
    protected $signature = 'factory:produce-request {request* : Solicitação livre do sistema a produzir}';
    protected $description = 'Produz um sistema a partir de uma solicitação livre.';

    public function handle(ProduceRequestPipeline $pipeline): int
    {
        $request = implode(' ', (array) $this->argument('request'));

        try {
            $report = $pipeline->run($request);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        $this->info('Factory Request Production finalizada.');
        $this->line('Produto resolvido: ' . $report['resolved_product']);
        $this->line('Status: ' . $report['status']);
        $this->line('Relatório: ' . $report['path']);
        $this->warn('Próximo comando: ' . $report['next_command']);

        return $report['status'] === 'finished' ? self::SUCCESS : self::FAILURE;
    }
}
