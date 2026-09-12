<?php

namespace App\Console\Commands;

use App\CommercialFactory\Services\CommercialFactoryIntakeService;
use Illuminate\Console\Command;
use Throwable;

class CommercialFactoryIntakeCommand extends Command
{
    protected $signature = 'commercial:factory-intake
        {product}
        {client}
        {--plan=start}
        {--email=}
        {--domain=}
        {--dry-run}
        {--approved : Confirma aprovação explícita para persistência}
        {--approval-token= : Token emitido na análise pré-aprovação}';

    protected $description = 'Conecta pedido comercial à Factory com gate explícito de aprovação.';

    public function handle(CommercialFactoryIntakeService $service): int
    {
        try {
            $report = $service->intake([
                'product' => (string) $this->argument('product'),
                'client' => (string) $this->argument('client'),
                'plan' => (string) $this->option('plan'),
                'email' => $this->option('email'),
                'domain' => $this->option('domain'),
            ], (bool) $this->option('dry-run'), (bool) $this->option('approved'), $this->option('approval-token'));
        } catch (Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info('Commercial → Factory Pipeline concluído.');
        $this->line('Status: ' . $report['status']);
        $this->line('Projeto: ' . $report['project_slug']);
        $this->line('Status comercial: ' . $report['commercial_status']);
        $this->line('Persistido: ' . (($report['persisted'] ?? false) ? 'sim' : 'não'));
        $this->line('Relatório: ' . ($report['path'] ?? '-'));

        if (($report['status'] ?? null) === 'awaiting_approval' && isset($report['approval_token'])) {
            $this->warn('Aprovação necessária. Reexecute com --approved e --approval-token=<token>.');
            $this->line('Approval token: ' . $report['approval_token']);
        }

        return in_array($report['status'] ?? null, ['awaiting_approval', 'approved_dry_run', 'awaiting_materialization'], true)
            ? self::SUCCESS
            : self::FAILURE;
    }
}
