<?php

declare(strict_types=1);

namespace App\Factory\Console\Commands;

use App\Factory\Workflow\Services\WorkflowDesigner;
use Illuminate\Console\Command;

class FactoryWorkflowCommand extends Command
{
    protected $signature = 'factory:workflow {domain*}';
    protected $description = 'Desenha um workflow para o domínio informado.';

    public function handle(WorkflowDesigner $designer): int
    {
        $workflow = $designer->design(implode(' ', (array) $this->argument('domain')));

        $this->info('Workflow Designer');
        $this->line('Domínio: ' . $workflow['domain']);

        $this->table(['Etapas'], array_map(fn ($step) => [$step], $workflow['steps']));
        $this->table(['De', 'Para'], array_map(fn ($transition) => [$transition['from'], $transition['to']], $workflow['transitions']));

        return self::SUCCESS;
    }
}
