<?php

declare(strict_types=1);

namespace App\Factory\Console\Commands;

use App\Factory\Architecture\Services\ArchitectureDesigner;
use Illuminate\Console\Command;
use Throwable;

class FactoryArchitectureDesignCommand extends Command
{
    protected $signature = 'factory:architecture-design {prompt* : Descrição do sistema}';
    protected $description = 'Gera um documento de arquitetura para um sistema solicitado.';

    public function handle(ArchitectureDesigner $designer): int
    {
        $prompt = implode(' ', (array) $this->argument('prompt'));

        try {
            $architecture = $designer->design($prompt);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        $this->info('Architecture Designer');
        $this->line('Nome: ' . $architecture['name']);
        $this->line('Slug: ' . $architecture['slug']);
        $this->line('Arquivo: ' . $architecture['path']);
        $this->line('Módulos: ' . count($architecture['modules']));
        $this->line('Componentes: ' . count($architecture['components']));

        $this->table(['Módulo', 'Componentes recomendados'], array_map(fn ($module) => [
            $module['slug'],
            implode(', ', array_map(fn ($component) => $component['label'], $module['recommended_components'])),
        ], $architecture['modules']));

        return self::SUCCESS;
    }
}
