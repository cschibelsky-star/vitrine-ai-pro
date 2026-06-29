<?php

declare(strict_types=1);

namespace App\Factory\Console\Commands;

use App\Factory\Architecture\Services\DomainKnowledgeEngine;
use Illuminate\Console\Command;

class FactoryDomainKnowledgeCommand extends Command
{
    protected $signature = 'factory:domain-knowledge {input* : Domínio ou descrição do sistema}';
    protected $description = 'Analisa conhecimento de domínio para uma solução.';

    public function handle(DomainKnowledgeEngine $engine): int
    {
        $result = $engine->analyze(implode(' ', (array) $this->argument('input')));

        $this->info('Domain Knowledge Engine');
        $this->line('Domínio: ' . $result['label']);
        $this->line('Chave: ' . $result['domain']);

        $this->table(['Módulos típicos'], array_map(fn ($module) => [$module], $result['modules']));
        $this->table(['Relacionamentos típicos'], array_map(fn ($item) => [$item], $result['typical_relationships']));

        return self::SUCCESS;
    }
}
