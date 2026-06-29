<?php

declare(strict_types=1);

namespace App\Factory\Console\Commands;

use App\Factory\Decision\Services\DecisionEngine;
use Illuminate\Console\Command;

class FactoryDecisionCommand extends Command
{
    protected $signature = 'factory:decision {prompt*}';
    protected $description = 'Executa o Decision Engine para uma solicitação.';

    public function handle(DecisionEngine $engine): int
    {
        $decision = $engine->decide(implode(' ', (array) $this->argument('prompt')));

        $this->info('Decision Engine');
        $this->line('Domínio: ' . $decision['domain']);
        $this->line('QA: ' . $decision['qa_level']);
        $this->line('Instalação: ' . $decision['install_mode']);

        $this->table(['Módulos'], array_map(fn ($item) => [$item], $decision['modules']));
        $this->table(['Componentes'], array_map(fn ($item) => [$item], $decision['components']));

        return self::SUCCESS;
    }
}
