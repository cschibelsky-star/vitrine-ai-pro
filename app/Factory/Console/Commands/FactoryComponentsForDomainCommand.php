<?php

declare(strict_types=1);

namespace App\Factory\Console\Commands;

use App\Factory\Architecture\Services\ComponentMarketplace;
use App\Factory\Architecture\Services\DomainKnowledgeEngine;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class FactoryComponentsForDomainCommand extends Command
{
    protected $signature = 'factory:components-for-domain {input* : Domínio ou descrição do sistema}';
    protected $description = 'Lista componentes reutilizáveis recomendados para um domínio.';

    public function handle(DomainKnowledgeEngine $domains, ComponentMarketplace $marketplace): int
    {
        $input = implode(' ', (array) $this->argument('input'));
        $domain = $domains->analyze($input);
        $components = $marketplace->componentsFor($domain['domain']);

        $this->info('Component Marketplace');
        $this->line('Domínio: ' . $domain['label']);

        $this->table(['Key', 'Componente', 'Tipo'], array_map(fn ($component) => [
            $component['key'],
            $component['label'],
            $component['type'],
        ], $components));

        return self::SUCCESS;
    }
}
