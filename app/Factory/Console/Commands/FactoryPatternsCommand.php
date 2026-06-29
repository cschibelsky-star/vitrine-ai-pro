<?php

declare(strict_types=1);

namespace App\Factory\Console\Commands;

use App\Factory\Learning\Services\PatternLibrary;
use Illuminate\Console\Command;

class FactoryPatternsCommand extends Command
{
    protected $signature = 'factory:patterns {slug : Slug do módulo gerado}';
    protected $description = 'Identifica padrões e sugestões para um módulo gerado.';

    public function handle(PatternLibrary $patterns): int
    {
        $result = $patterns->inspect((string) $this->argument('slug'));

        $this->info('Padrões identificados');
        $this->line('Módulo: ' . $result['module']);
        $this->line('Patterns: ' . implode(', ', $result['patterns']));

        $this->table(['Sugestões'], array_map(fn ($item) => [$item], $result['suggestions']));

        return self::SUCCESS;
    }
}
