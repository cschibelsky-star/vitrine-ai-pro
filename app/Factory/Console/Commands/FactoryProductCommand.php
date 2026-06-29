<?php

declare(strict_types=1);

namespace App\Factory\Console\Commands;

use App\Factory\Products\Services\ProductGenerator;
use Illuminate\Console\Command;
use Throwable;

class FactoryProductCommand extends Command
{
    protected $signature = 'factory:product {key}';
    protected $description = 'Gera manifesto de produto oficial da Vitrine AI Pro.';

    public function handle(ProductGenerator $generator): int
    {
        try {
            $manifest = $generator->generate((string) $this->argument('key'));
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        $this->info('Product Generator');
        $this->line('Produto: ' . $manifest['name']);
        $this->line('Domínio: ' . $manifest['domain']);
        $this->line('Arquivo: ' . $manifest['path']);

        $this->table(['Módulo'], array_map(fn ($module) => [$module], $manifest['modules']));
        $this->table(['Componente'], array_map(fn ($component) => [$component], $manifest['components']));

        return self::SUCCESS;
    }
}
