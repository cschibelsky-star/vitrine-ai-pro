<?php

declare(strict_types=1);

namespace App\Factory\Console\Commands;

use App\Factory\FinalProducer\Services\ProduceRequestPipeline;
use Illuminate\Console\Command;
use Throwable;

class FactoryProduceRequestCommand extends Command
{
    protected $signature = 'factory:produce-request {request* : Solicitação livre do sistema a produzir} {--approved : Confirma aprovação explícita para produção}';
    protected $description = 'Prepara ou produz um sistema a partir de uma solicitação livre usando o Factory Intelligence Core.';

    public function handle(ProduceRequestPipeline $pipeline): int
    {
        $request = implode(' ', (array) $this->argument('request'));

        try {
            $report = $pipeline->run($request, (bool) $this->option('approved'));
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        $this->info('Factory Request Pipeline concluído.');
        $this->line('Domínio: ' . ($report['domain'] ?? 'n/d'));
        $this->line('Produto resolvido: ' . ($report['resolved_product'] ?? 'nenhum'));
        $this->line('Status: ' . $report['status']);
        $this->line('Relatório: ' . $report['path']);
        $this->warn('Próximo passo: ' . $report['next_command']);

        return in_array($report['status'], ['finished', 'awaiting_approval', 'awaiting_materialization'], true)
            ? self::SUCCESS
            : self::FAILURE;
    }
}
